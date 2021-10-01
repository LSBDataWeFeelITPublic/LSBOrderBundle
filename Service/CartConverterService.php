<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Service;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use LSB\ContractorBundle\Entity\ContractorInterface;
use LSB\LocaleBundle\Manager\TaxManager;
use LSB\OrderBundle\CartException\CartConverterException;
use LSB\OrderBundle\CartHelper\PriceHelper;
use LSB\OrderBundle\CartHelper\QuantityHelper;
use LSB\OrderBundle\Entity\Cart;
use LSB\OrderBundle\Entity\CartInterface;
use LSB\OrderBundle\Entity\CartItem;
use LSB\OrderBundle\Entity\CartPackage;
use LSB\OrderBundle\Entity\CartPackageItem;
use LSB\OrderBundle\Entity\Order;
use LSB\OrderBundle\Entity\OrderInterface;
use LSB\OrderBundle\Entity\OrderPackageInterface;
use LSB\OrderBundle\Entity\OrderPackageItem;
use LSB\OrderBundle\Entity\PackageItem;
use LSB\OrderBundle\Entity\PackageItemInterface;
use LSB\OrderBundle\Manager\CartItemManager;
use LSB\OrderBundle\Manager\CartPackageItemManager;
use LSB\OrderBundle\Manager\OrderManager;
use LSB\OrderBundle\Manager\OrderPackageItemManager;
use LSB\OrderBundle\Manager\OrderPackageManager;
use LSB\OrderBundle\Repository\CartItemRepositoryInterface;
use LSB\OrderBundle\Repository\CartPackageRepositoryInterface;
use LSB\PaymentBundle\Manager\PaymentManager;
use LSB\PricelistBundle\Manager\PricelistManager;
use LSB\PricelistBundle\Service\TotalCalculatorManager;
use LSB\ProductBundle\Entity\Product;
use LSB\OrderBundle\Entity\OrderPackage;
use LSB\ProductBundle\Entity\ProductInterface;
use LSB\ProductBundle\Entity\ProductSetProduct;
use LSB\UserBundle\Entity\UserInterface;
use LSB\UtilityBundle\Helper\ValueHelper;
use LSB\UtilityBundle\Repository\RepositoryInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Workflow\Registry;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class CartConverterService
 * @package LSB\CartBundle\Service
 */
class CartConverterService
{
    const PRODUCT_SET_ORDERCODE_PREFIX = 'productSet';

    const PRODUCT_SET_ORDERCODE_SEPARATOR = '|';

    const EXTENDED_DATA_CART_TOTAL_NETTO = 'cartTotalNetto';

    const EXTENDED_DATA_CART_TOTAL_GROSS = 'cartTotalGross';

    public function __construct(
        protected EntityManagerInterface $manager,
        protected TokenStorageInterface $tokenStorage,
        protected Registry $workflowRegistry,
        protected TranslatorInterface $translator,
        protected EventDispatcherInterface $dispatcher,
        protected RequestStack $requestStack,
        protected CartService $cartService,
        protected PriceListManager $priceListManager,
        protected ParameterBagInterface $ps,
        protected TotalCalculatorManager $totalCalculatorManager,
        protected PaymentManager $paymentManager,
        protected CartPackageItemManager $cartPackageItemManager,
        protected CartItemManager $cartItemManager,
        protected OrderPackageManager $orderPackageManager,
        protected OrderManager $orderManager,
        protected OrderPackageItemManager $orderPackageItemManager,
        protected PriceHelper $priceHelper,
        protected QuantityHelper $quantityHelper
    )
    {

    }


    /**
     * @return CartPackageRepositoryInterface
     */
    public function getPackageItemsRepository(): RepositoryInterface
    {
        return $this->cartPackageItemManager->getRepository();
    }


    /**
     * @return CartItemRepositoryInterface|RepositoryInterface
     */
    public function getCartItemRepository()
    {
        return $this->cartItemManager->getRepository();
    }

    /**
     * @param CartInterface $cart
     * @param Order|null $order
     * @param ContractorInterface|null $customerDelivery
     * @param bool $finalizeCart
     * @return Order|null
     * @throws \Exception
     */
    public function convertCartIntoOrder(
        Cart $cart,
        Order $order = null,
        ContractorInterface $customerDelivery = null,
        bool $finalizeCart = true
    ): ?Order {
        $user = $cart->getUser();
        $convertedProductSets = [];

        $cartTotalGrossBeforeProductSetSplit = null;

        //Odświeżamy wyliczenie koszyka
        $this->cartService->getCartSummary($cart, true);
        //Pobieramy wartość koszyka przed rozbiciem produktów na składowe, wartośc po konwersji nie powinna posiadać różnicy
        $cartTotalGrossBeforeProductSetSplit = $cart->getCartSummary()->getTotalGross(true);

        // Konwersja pozycji w koszyku na elementy składowe zestawu
        /**
         * @var \LSB\OrderBundle\Entity\CartItem $cartItem
         */
        foreach ($cart->getCartItems() as $key => $cartItem) {
            if (!$cartItem->isSelected()) {
                continue;
            }

            //Jeżeli mamy do czynienia z zestawem i jest on wybrany do konwersji, dokonujemy jego rozbicia na elementy składowe
            $productSet = $this->convertProductSetCartItemIntoCartItems($cart, $cartItem);

            if ($productSet instanceof Product) {
                $convertedProductSets[$productSet->getUuid()] = [
                    'productSet' => $productSet,
                    'quantity' => $cartItem->getQuantity()
                ];
            }
        }



        //Czyścimy kolekcję pozycji
        $cart->getCartItems()->setInitialized(false);
        //Pobieramy pozycje na nowo

        $cartItemsConvertedIntoOrder = [];


        //Przed rozbicie paczek na dostawców należy uwzględnić koszt dostawy
        //$this->cartService->splitPackagesForSuppliers($cart, true);

        if (!$order) {
            $order = $this->orderManager->createNew();
            $order->setNumber('randomNumber'.microtime()); //TODO use numbering bundle
        }

        /**
         * @var Order $order
         */
        $order
            ->setUser($cart->getUser())
            ->setBillingContractor($cart->getBillingContractor());

        // Sprawdzamy czy produkty są na pewno dostępne do konwersji
        /**
         * @var CartItem $cartItem
         */
        foreach ($cart->getCartItems() as $key => $cartItem) {
            if (!$cartItem->isSelected()) {
                continue;
            }

            if (!$cartItem->getProduct() instanceof Product) {
                throw new CartConverterException("CartItem does not have relation to product. Please clear cart. CartItem ID: {$cartItem->getId()}");
            }

            //zapisujemy pozycje, które nie zostały pominięte
            $cartItemsConvertedIntoOrder[$cartItem->getId()] = $cartItem;
        }

        //Odświeżamy wyliczenie koszyka
        $cart->clearCartSummary();
        $this->cartService->getCartSummary($cart, true);

        //Weryfikujemy wartość koszyka
        if (abs((int) $cartTotalGrossBeforeProductSetSplit->getAmount() - (int) $cart->getCartSummary()->getTotalGross(true)->getAmount()) > 0.01) {
            //W przypadku wykrycia różnic czyścimy pozycje, pozostawiając resztę danych
            $this->cartService->closeCart($cart);
            $this->cartService->updateCart($cart);

            throw new CartConverterException("Wrong cart total gross after product set split. Check product set configuration. Cart ID: {$cart->getId()}");
        }

        //Podsumowanie
        $order
            //->setCart($cart)
            ->setTotalValueNet($cart->getCartSummary()->getTotalNet(true))
            ->setTotalValueGross($cart->getCartSummary()->getTotalGross(true))
            ->setProductsValueNet($cart->getCartSummary()->getTotalProductsNet(true))
            ->setProductsValueGross($cart->getCartSummary()->getTotalProductsGross(true))
            ->setShippingCostNet($cart->getCartSummary()->getShippingCostNet(true))
            ->setShippingCostGross($cart->getCartSummary()->getShippingCostGross(true))
            ->setPaymentCostNet($cart->getCartSummary()->getPaymentCostNet(true))
            ->setPaymentCostGross($cart->getCartSummary()->getPaymentCostGross(true))
            ->setCurrency($cart->getCurrency())
            ->setCurrencyIsoCode($cart->getCurrencyIsoCode())
            //TODO check
            ->setBillingContractorVatStatus(null)
            ->setCalculationType($cart->getCalculationType())
            ->setRealisationAt($cart->getRealisationAt())
            ->setClientOrderNumber($cart->getClientOrderNumber())
            ->setProcessingType($cart->getProcessingType())
        ;


//            ->setOrderVerificationNotes($cart->getOrderVerificationNotes())
//            ->setInvoiceNotes($cart->getInvoiceNotes())
//            ->setShowPrices(true)
//            ->setInvoiceEmail($cart->getUseCustomerRecipient() && $cart->getCustomerRecipientEinvoiceEmail() ? $cart->getCustomerRecipientEinvoiceEmail() : $cart->getInvoiceEmail())
//            ->setEmailInvoiceAgree($cart->getEmailInvoiceAgree())
//            ->setEmailInvoiceAgreeDate($cart->getEmailInvoiceAgreeDate())
//            ->setTermsAgree($cart->getTermsAgree())
//            ->setTermsAgreeDate($cart->getTermsAgreeDate())
//            ->setPersonalDataOrderProcessingAgree($cart->getPersonalDataOrderProcessingAgree())
//            ->setPersonalDataOrderProcessingAgreeDate($cart->getPersonalDataOrderProcessingAgreeDate())
//            ->setPrivacyPolicyAgree($cart->getPrivacyPolicyAgree())
//            ->setPrivacyPolicyAgreeDate($order->getPrivacyPolicyAgreeDate())
//            ->setPaymentMethod($cart->getPaymentMethod())
//            ->setIsOrderVerificationRequested($cart->getIsOrderVerificationRequested())
//            ->setSuggestedCustomer($cart->getSuggestedCustomer())
//            ->setIsCustomerBillingDataChanged($cart->getIsCustomerBillingDataChanged())
//        ;

        //Zapis danych historycznych

//        $this->createOrderNotes($cart, $order, $user);
//        $this->createOrderNoteWithConvertedProductSets($order, $convertedProductSets);
//        $this->rewriteCustomerContactPerson($cart, $order);
//        $this->rewriteAndVerifyCustomerRecipient($cart, $order);
//        $this->rewriteCustomerData($cart);
//        $this->rewriteShopUserData($cart);

        $i = 1;


        /**
         * @var CartPackage $package
         */
        foreach ($cart->getCartPackages() as $package) {
            $orderPackage = $this->createOrderPackageFromCartPackage(
                $order,
                $package,
                true,
                true
            );

            $orderPackage->generateOrderPackageNumber($i);
            $order->addOrderPackage($orderPackage);
            $i++;
        }

        $this->totalCalculatorManager->calculateTotal($order);

        //TODO FIX

//        dump("Cart:");
//        dump($cart->getCartSummary()->getTotalNet(true));
//        dump($cart->getCartSummary()->getTotalGross(true));
//        dump($cart->getCartSummary()->getPaymentCostNet(true));
//        dump($cart->getCartSummary()->getShippingCostNet(true));
//
//
//        dump("Order:");
//        dump($order->getTotalValueNet(true));
//        dump($order->getTotalValueGross(true));
//        dump($order->getPaymentCostNet(true));
//        dump($order->getShippingCostNet(true));
//
//        die("X");

        if (abs((int)$cartTotalGrossBeforeProductSetSplit->getAmount() - (int) $order->getTotalValueGross(true)->getAmount()) > 1) {
            $order->setStatus(OrderInterface::STATUS_CANCELED);
            $this->cartService->closeCart($cart);
            $this->manager->flush();
            throw new CartConverterException("Wrong order total gross after cart to order conversion. Check product set configuration. Cart ID: {$cart->getId()}, {}");
        }

//        switch ($cart->getInvoiceDeliveryType()) {
//            case \LSB\CartBundle\Entity\Cart::INVOICE_DELIVERY_USE_NEW_ADDRESS:
//                $order
//                    ->setInvoiceDeliveryName($cart->getInvoiceDeliveryName())
//                    ->setInvoiceDeliveryAddress($cart->getInvoiceDeliveryAddress())
//                    ->setInvoiceDeliveryHouseNumber($cart->getInvoiceDeliveryHouseNumber())
//                    ->setInvoiceDeliveryZipCode($cart->getInvoiceDeliveryZipCode())
//                    ->setInvoiceDeliveryCity($cart->getInvoiceDeliveryCity());
//                break;
//            case Cart::INVOICE_DELIVERY_USE_CUSTOMER_DATA:
//                $order
//                    ->setInvoiceDeliveryName($order->getCustomerName())
//                    ->setInvoiceDeliveryAddress($order->getCustomerAddress())
//                    ->setInvoiceDeliveryHouseNumber($order->getCustomerHouseNumber())
//                    ->setInvoiceDeliveryZipCode($order->getCustomerZipCode())
//                    ->setInvoiceDeliveryCity($order->getCustomerCity());
//                break;
//        }

        $this->manager->persist($order);
        //$orderWorkflow = $this->workflowRegistry->get($order, "shop_order_processing");

        try {
//            if ($orderWorkflow->can($order, 'configure')) {
//                $orderWorkflow->apply($order, 'configure');
//            }

            //Wymagany po utworzeniu zamówienia
            $this->cartService->updateCart($cart);

            $cart->addOrder($order);
        } catch (\Exception $e) {
            $order = null;
        }

        if ($cart->getAuthType() === CartInterface::AUTH_TYPE_REGISTRATION) {
            //Jeżeli w trakcie składania zamówienia wybrano utworzenie konta generuje email z linkiem potwierdzającym założenie konta
//            $this->sendAccountConfirmation($user);
        }

        if ($finalizeCart && $order instanceof Order) {
            $this->finalizeCart($cart, $cartItemsConvertedIntoOrder);
        }

        if ($order instanceof Order) {
            $this->orderManager->update($order);
        }

        return $order;
    }


    /**
     * @param OrderInterface $order
     * @param CartPackage $cartPackage
     * @param bool $addShippingProductItem
     * @param bool $addPaymentProductItem
     * @return OrderPackageInterface
     * @throws \Exception
     */
    public function createOrderPackageFromCartPackage(
        OrderInterface $order,
        CartPackage $cartPackage,
        bool $addShippingProductItem = true,
        bool $addPaymentProductItem = true
    ): OrderPackageInterface {
        $orderPackage = $this->orderPackageManager->createNew();
        $orderPackage->setOrder($order);

        $position = PackageItemInterface::FIRST_POSITION;
        $totalRes = [];
        $this->rewriteDeliveryDataFromCartPackage($orderPackage, $cartPackage);

        $orderPackage
            ->setIsChargedForShipping($cartPackage->isChargedForShipping())
            ->setType($cartPackage->getType());

        /**
         * @var CartPackageItem $cartPackageItem
         */
        foreach ($cartPackage->getCartPackageItems() as $cartPackageItem) {
            $this->createOrderPackageItemFromPackageItem($cartPackageItem, $orderPackage, $position, $totalRes);
            $position++;
        }

        //zaokrąglamy na samym końcu
        if ($this->ps->get('cart.calculation.gross')) {
            [$totalProductsNetto, $totalProductsGross] = TaxManager::calculateMoneyTotalNettoAndGrossFromGrossRes(
                $order->getCurrencyIsoCode(),
                $totalRes,
                true//TODO $this->cartManager->addTax($cartPackage->getCart())
            );
        } else {
            [$totalProductsNetto, $totalProductsGross] = TaxManager::calculateMoneyTotalNettoAndGrossFromNettoRes(
                $order->getCurrencyIsoCode(),
                $totalRes,
                true//TODO $this->cartManager->addTax($cartPackage->getCart())
            );
        }

        if ($addShippingProductItem) {
            // Shipping product package item
            $calculation = $this->cartService->calculatePackageShippingCost(
                $cartPackage,
                true,
                $this->ps->get('cart.calculation.gross') ? $order->getProductsValueGross(true) : $order->getProductsValueNet(true)
            );

            //Add shipping product
            $shippingPackageItem = $this->orderPackageItemManager->createNew();
            $shippingPackageItem
                ->setType(PackageItemInterface::TYPE_SHIPPING)
                ->setProduct($calculation->getShippingMethod()->getProduct())
                ->setProductName($calculation->getShippingMethod()->getProduct()?->getName())
                ->setProductNumber($calculation->getShippingMethod()->getProduct()?->getNumber())
                ->setProductType($calculation->getShippingMethod()->getProduct()?->getType())
                ->setPriceNet($calculation->getPriceNet())
                ->setPriceGross($calculation->getPriceGross())
                ->setValueNet($calculation->getPriceNet())
                ->setValueGross($calculation->getPriceGross())
                ->setTaxRate($calculation->getTaxPercentage())
                ->setQuantity($calculation->getCalculationQuantity());

            $orderPackage->addOrderPackageItem($shippingPackageItem);

            $orderPackage
                ->setPaymentCostNet($calculation?->getPriceNet())
                ->setPaymentCostGross($calculation?->getPriceGross())
                ->setPaymentCostTaxRate($calculation?->getTaxPercentage());
        }

        //Payment

        if ($addPaymentProductItem) {
            // Shipping product package item
            $calculation = $this->cartService->calculateCartPaymentCost(
                $cartPackage->getCart(),
                true,
                $this->ps->get('cart.calculation.gross') ? $order->getProductsValueGross(true) : $order->getProductsValueNet(true)
            );

            //Add shipping product
            $paymentPackageItem = $this->orderPackageItemManager->createNew();
            $paymentPackageItem
                ->setType(PackageItemInterface::TYPE_PAYMENT)
                ->setProduct($calculation->getPaymentMethod()->getProduct())
                ->setProductName($calculation->getPaymentMethod()->getProduct()?->getName())
                ->setProductNumber($calculation->getPaymentMethod()->getProduct()?->getNumber())
                ->setProductType($calculation->getPaymentMethod()->getProduct()?->getType())
                ->setPriceNet($calculation->getPriceNet())
                ->setPriceGross($calculation->getPriceGross())
                ->setValueNet($calculation->getPriceNet())
                ->setValueGross($calculation->getPriceGross())
                ->setTaxRate($calculation->getTaxPercentage())
                ->setQuantity($calculation->getCalculationQuantity());

            $orderPackage->addOrderPackageItem($paymentPackageItem);

            $orderPackage
                ->setShippingCostNet($calculation?->getPriceNet())
                ->setShippingCostGross($calculation?->getPriceGross())
                ->setShippingCostTaxRate($calculation?->getTaxPercentage());
        }

        //There is no need to rewrite prices at this moment
        //Order total calculator will recalculate exact value of the order packages at the end of the cart->order conversion process


        $orderPackage
            //->setTotalValueNet($totalProductsNetto)
            //->setTotalValueGross($totalProductsGross)
            ->setProductsValueNet($totalProductsNetto)
            ->setProductsValueGross($totalProductsGross)
            ->setShippingMethod($cartPackage->getShippingMethod())
            ->setShippingDays($cartPackage->getShippingDays())
        ;

        return $orderPackage;
    }

    /**
     * @param OrderPackage $orderPackage
     * @param CartPackage $cartPackage
     * @return OrderPackage
     * @throws \Exception
     */
    protected function rewriteDeliveryDataFromCartPackage(
        OrderPackage $orderPackage,
        CartPackage $cartPackage
    ): OrderPackage {
        if (!$cartPackage->getCart() instanceof CartInterface) {
            throw new \Exception('Cart is required.');
        }

        $cartPackageDeliveryAddress = $cartPackage->getDeliveryAddress();
        $orderPakcagerDeliveryAddress = clone $cartPackageDeliveryAddress;

        $cartPackagerContactPersonAddress = $cartPackage->getContactPersonAddress();
        $orderPackageContactPersonAddress = clone $cartPackagerContactPersonAddress;

        $orderPackage
            ->setDeliveryAddress($orderPakcagerDeliveryAddress)
            ->setContactPersonAddress($orderPackageContactPersonAddress)
            ->setSupplier($cartPackage->getSupplier())
        ;

//
//            //Notatki
//            ->setDeliveryNotes($cartPackage->getDeliveryNotes())
//            //Dane dostawcy
//            ->setSupplier($cartPackage->getSupplier())
//            ->setSupplierCode($cartPackage->getSupplierCode())
//            ->setSupplierName($cartPackage->getSupplierName())
//            ->setSupplierNumber($cartPackage->getSupplierNumber())
//            ->setSupplierType($cartPackage->getSupplierType())
//            ->setSupplierEmail($cartPackage->getSupplierEmail())
        ;

        return $orderPackage;
    }

    /**
     * @param Cart $cart
     * @param array $cartItemsConvertedIntoOrder
     * @return Cart|null
     * @throws \Exception
     */
    public function finalizeCart(
        Cart $cart,
        array $cartItemsConvertedIntoOrder = []
    ): ?Cart {

        if (!$cart) {
            $cart = $this->cartService->getCart();
        }

        if ($this->ps->get('cart.finalize.allow_cart_clone')
            && count($cartItemsConvertedIntoOrder) > 0
            && count($cartItemsConvertedIntoOrder) < $cart->getCartItems()->count()
        ) {
            $newOpenCart = clone $cart;

            $newOpenCart
                ->setTotalValueNet(null)
                ->setTotalValueGross(null)
                ->setDeliveryVariant(null)
                ->setSelectedDeliveryVariant(null)
                ->setNote(null)
                ->setValidatedStep(null)
                ->setSuggestedDeliveryVariant(null)
            ;

            $newOpenCart->getCartPackages()->clear();
            $newOpenCart->getOrders()->clear();


            /**
             * @var CartItem $cartItem
             */
            foreach ($cart->getCartItems() as $cartItem) {
                if (!array_key_exists($cartItem->getId(), $cartItemsConvertedIntoOrder)) {
                    $newOpenCart->removeCartItem($cartItem);
                }
            }

            /**
             * @var CartItem $cartItem
             */
            foreach ($newOpenCart->getCartItems() as $cartItem) {
                if (array_key_exists($cartItem->getId(), $cartItemsConvertedIntoOrder)) {
                    $newOpenCart->removeCartItem($cartItem);
                }
            }
            $this->manager->persist($newOpenCart);
        }

        $cart
            ->setValidatedStep(CartInterface::CART_STEP_ORDER_CREATED);

        //$this->cartManager->clearSessionId();

        //$this->cartManager->setSessionRulesAccepted(false);

        return $cart;
    }


    /**
     * @return UserInterface|null
     * @throws \Exception
     */
    protected function getUser(): ?UserInterface
    {
        if ($this->tokenStorage && $this->tokenStorage->getToken() && $this->tokenStorage->getToken()->getUser() instanceof UserInterface) {
            $user = $this->tokenStorage->getToken()->getUser();

            if (!$user) { //!$this->ps->getParameter('cart.configuration.not_logged.enabled')
                throw new \Exception('User not logged in.');
            }

            return $user;
        }

        return null;
    }

    /**
     * @param CartPackageItem $cartPackageItem
     * @param OrderPackage $orderPackage
     * @param $position
     * @param array $totalRes
     * @return PackageItem
     * @throws \Exception
     */
    protected function createOrderPackageItemFromPackageItem(
        CartPackageItem $cartPackageItem,
        OrderPackage $orderPackage,
        $position,
        array &$totalRes
    ): PackageItem {

        /**
         * @var OrderPackageItem $orderPackagetItem
         */
        $orderPackageItem = $this->orderPackageItemManager->createNew();

        $orderPackageItem
            ->setCurrency($cartPackageItem->getCurrency())
            ->setCurrencyIsoCode($cartPackageItem->getCurrencyIsoCode())
            ->setOrderPackage($orderPackage)
            ->setPosition($position)
            ->setCatalogPriceNet($cartPackageItem->getCartItem()->getCartItemSummary()->getBasePriceNet())
            ->setCatalogPriceGross($cartPackageItem->getCartItem()->getCartItemSummary()->getBasePriceGross())
            ->setPriceNet($cartPackageItem->getCartItem()->getCartItemSummary()->getPriceNet())
            ->setPriceGross($cartPackageItem->getCartItem()->getCartItemSummary()->getPriceGross())
            ->setValueNet($cartPackageItem->getCartItem()->getCartItemSummary()->getValueNet())
            ->setValueGross($cartPackageItem->getCartItem()->getCartItemSummary()->getValueGross())
            ->setTaxRate($cartPackageItem->getCartItem()->getCartItemSummary()->getTaxRate() ?? ValueHelper::convertToValue(23)) //TODO fixed
            ->setProduct($cartPackageItem->getProduct())
            ->setQuantity($cartPackageItem->getQuantity(true))
        ;

        $orderPackageItem->getProductData()->setName($cartPackageItem->getProduct()?->getName());
        $orderPackageItem->getProductData()->setNumber($cartPackageItem->getProduct()?->getNumber());
        $orderPackageItem->getProductData()->setType($cartPackageItem->getProduct()?->getType());


        $orderPackageItem
            ->setType($cartPackageItem->getType());
            //->recalculateDiscount()
        ;

        $orderPackage->addOrderPackageItem($orderPackageItem);

        //TODO move to configuration tree builder
        if ($this->ps->get('cart.calculation.gross')) {
            $valueNetto = $this->priceHelper->calculateMoneyNetValueFromGrossPrice(
                $orderPackageItem->getPriceGross(true),
                $orderPackageItem->getQuantity(true),
                $orderPackageItem->getTaxRate(true)
            );
            $valueGross = $this->priceHelper->calculateMoneyGrossValue(
                $orderPackageItem->getPriceGross(true),
                $orderPackageItem->getQuantity(true)
            );
            TaxManager::addMoneyValueToGrossRes($orderPackageItem->getTaxRate(true), $valueGross, $totalRes);
        } else {
            $valueNetto = $this->priceHelper->calculateMoneyNetValue(
                $orderPackageItem->getPriceNet(true),
                $orderPackageItem->getQuantity(true)
            );
            $valueGross = $this->priceHelper->calculateMoneyGrossValueFromNetPrice(
                $orderPackageItem->getPriceNet(true),
                $orderPackageItem->getQuantity(true),
                $orderPackageItem->getTaxRate(true)
            );
            TaxManager::addMoneyValueToNettoRes($orderPackageItem->getTaxRate(true), $valueNetto, $totalRes);
        }

        //Saved values for
        $cartPackageItem
            ->setValueNet($valueNetto)
            ->setValueGross($valueGross);

        return $orderPackageItem;
    }

    /**
     * TODO?
     *
     * @param Order $order
     * @return array
     */
    protected function compareOrderBillingDataWithCustomerBillingData(Order $order): array
    {
        return [];
    }

    /**
     * @param Cart $cart
     * @param CartItem $cartItem
     * @return ProductInterface|null
     * @throws \Exception
     */
    protected function convertProductSetCartItemIntoCartItems(Cart $cart, CartItem $cartItem): ?ProductInterface
    {
        if (!$cartItem->getProduct() || !$cartItem->getProduct()->isProductSet()) {
            return null;
        }

        //Usumamy zestaw z produktu
        $productSet = $cartItem->getProduct();
        $productSetQuantity = $cartItem->getQuantity();

        $productSetProducts = $productSet->getProductSetProducts();

        $updateData = [];

        $updateData[] = [
            'uuid' => $cartItem->getProduct()->getUuid(),
            'quantity' => 0
        ];

        /**
         * @var ProductSetProduct $productSetProduct
         */
        foreach ($productSetProducts as $productSetProduct) {
            if (!$productSetProduct->getProduct() || !$productSetProduct->getProductSet()) {
                continue;
            }

            $product = $productSetProduct->getProduct();
            $productSet = $productSetProduct->getProductSet();

            $updateData[] = [
                'uuid' => $product->getUuid(),
                'quantity' => $productSetProduct->getQuantity() ? $productSetQuantity * $productSetProduct->getQuantity() : $productSetQuantity,
                'ordercode' => $this->prepareOrderCodeForProductSet($cartItem, $productSetProduct->getProductSet()),
                'productSetUuid' => $productSet->getUuid(),
                'productSetQuantity' => $productSetQuantity
            ];
        }

        $this->cartService->updateCartItems($cart, $updateData, false);

        return $productSet;
    }

    /**
     * @param CartItem $cartItem
     * @param Product $productSet
     * @return string|null
     */
    protected function prepareOrderCodeForProductSet(CartItem $cartItem, Product $productSet): ?string
    {
        $cartItemOrderCode = $cartItem->getOrderCode();
        $productSetOrderCode = self::PRODUCT_SET_ORDERCODE_PREFIX . $productSet->getUuid();
        $hasProductSetOrderCode = mb_strpos($cartItemOrderCode, static::PRODUCT_SET_ORDERCODE_PREFIX);

        if ($cartItemOrderCode && $hasProductSetOrderCode === false) {
            return $cartItemOrderCode . static::PRODUCT_SET_ORDERCODE_SEPARATOR . $productSetOrderCode;
        }

        return $productSetOrderCode;
    }

    /**
     * @param Order $order
     * @param array $productSets
     * @throws \Exception
     * @deprecated
     */
    protected function createOrderNoteWithConvertedProductSets(Order $order, array $productSets): void
    {
        return;
    }

    /**
     * TODO
     *
     * @param Order $order
     * @param array $productSets
     * @return bool
     */
    public function validateOrderProductSetConversion(Order $order, array $productSets): bool
    {
        return true;
    }

    /**
     * TODO
     *
     * @param Cart $cart
     * @param array $productSetsData
     * @return bool
     */
    public function validateCartItemProductSetConversion(Cart $cart, array $productSetsData): bool
    {
        return true;
    }
}
