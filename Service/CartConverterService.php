<?php

namespace LSB\OrderBundle\Service;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use LSB\ContractorBundle\Entity\ContractorInterface;
use LSB\LocaleBundle\Manager\TaxManager;
use LSB\OrderBundle\Entity\Cart;
use LSB\OrderBundle\Entity\CartInterface;
use LSB\OrderBundle\Entity\CartItem;
use LSB\OrderBundle\Entity\CartPackage;
use LSB\OrderBundle\Entity\CartPackageInterface;
use LSB\OrderBundle\Entity\CartPackageItem;
use LSB\OrderBundle\Entity\Order;
use LSB\OrderBundle\Entity\OrderInterface;
use LSB\OrderBundle\Entity\OrderNote;
use LSB\OrderBundle\Entity\OrderPackageInterface;
use LSB\OrderBundle\Entity\OrderPackageItem;
use LSB\OrderBundle\Entity\PackageItem;
use LSB\OrderBundle\Entity\PackageItemInterface;
use LSB\OrderBundle\Event\OrderEvent;
use LSB\OrderBundle\Event\OrderEvents;
use LSB\OrderBundle\Manager\CartItemManager;
use LSB\OrderBundle\Manager\CartManager;
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
use LSB\ProductBundle\Entity\ProductSetProduct;
use LSB\OrderBundle\Entity\OrderPackage;
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

    //podstawowe serwisy

    protected EntityManagerInterface $manager;

    protected TokenStorageInterface $tokenStorage;

    /** @var Registry */
    protected Registry $workflowRegistry;

    protected TranslatorInterface $translator;

    /** @var EventDispatcherInterface */
    protected EventDispatcherInterface $eventDispatcher;

    protected CartManager $cartManager;

    protected PricelistManager $pricelistManager;

    protected PaymentManager $paymentManager;

    protected ParameterBagInterface $ps;

    protected TotalCalculatorManager $totalCalculatorManager;

    /**
     * @var RequestStack
     */
    protected RequestStack $requestStack;

    protected CartPackageItemManager $cartPackageItemManager;

    protected CartItemManager $cartItemManager;

    protected OrderPackageManager $orderPackageManager;

    protected OrderPackageItemManager $orderPackageItemManager;

    protected OrderManager $orderManager;

    /**
     * @param EntityManager $manager
     * @param TokenStorageInterface $tokenStorage
     * @param Registry $workflowRegistry
     * @param TranslatorInterface $translator
     * @param EventDispatcherInterface $dispatcher
     * @param RequestStack $requestStack
     */
    public function setCoreServices(
        EntityManager $manager,
        TokenStorageInterface $tokenStorage,
        Registry $workflowRegistry,
        TranslatorInterface $translator,
        EventDispatcherInterface $dispatcher,
        RequestStack $requestStack
    ): void {
        $this->manager = $manager;
        $this->tokenStorage = $tokenStorage;
        $this->workflowRegistry = $workflowRegistry;
        $this->translator = $translator;
        $this->eventDispatcher = $dispatcher;
        $this->requestStack = $requestStack;
    }

    /**
     * @param CartManager $cartManager
     * @param PricelistManager $priceListManager
     * @param ParameterBagInterface $ps
     * @param TotalCalculatorManager $totalCalculatorManager
     * @param PaymentManager $paymentManager
     * @param CartPackageItemManager $cartPackageItemManager
     * @param CartItemManager $cartItemManager
     * @param OrderPackageManager $orderPackageManager
     */
    public function setAdditionalServices(
        CartManager $cartManager,
        PriceListManager $priceListManager,
        ParameterBagInterface $ps,
        TotalCalculatorManager $totalCalculatorManager,
        PaymentManager $paymentManager,
        CartPackageItemManager $cartPackageItemManager,
        CartItemManager $cartItemManager,
        OrderPackageManager $orderPackageManager,
        OrderManager $orderManager
    ): void {
        $this->cartManager = $cartManager;
        $this->pricelistManager = $priceListManager;
        $this->ps = $ps;
        $this->totalCalculatorManager = $totalCalculatorManager;
        $this->paymentManager = $paymentManager;
        $this->cartPackageItemManager = $cartPackageItemManager;
        $this->cartItemManager = $cartItemManager;
        $this->orderPackageManager = $orderPackageManager;
        $this->orderManager = $orderManager;
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
     */
    public function convertCartIntoOrder(
        CartInterface $cart,
        Order $order = null,
        ContractorInterface $customerDelivery = null,
        bool $finalizeCart = true
    ): ?Order {
        //TODO
        return null;
    }


    /**
     * @param OrderInterface $order
     * @param CartPackageInterface $cartPackage
     * @return OrderPackageInterface
     * @throws \Exception
     */
    public function createOrderPackageFromCartPackage(
        OrderInterface $order,
        CartPackage $cartPackage
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
            [$totalProductsNetto, $totalProductsGross] = TaxManager::calculateTotalNettoAndGrossFromGrossRes(
                $totalRes,
                true//TODO $this->cartManager->addTax($cartPackage->getCart())
            );
        } else {
            [$totalProductsNetto, $totalProductsGross] = TaxManager::calculateTotalNettoAndGrossFromNettoRes(
                $totalRes,
                true//TODO $this->cartManager->addTax($cartPackage->getCart())
            );
        }
// TODO shipping calculation
//        $calculation = $this->cartManager->calculatePackageShippingCost(
//            $cartPackage,
//            $order->getTotalProducts()
//        );

        $orderPackage
            ->setTotalNet($totalProductsNetto)
            ->setTotalGross($totalProductsGross)
            ->setTotalProductsNet($totalProductsNetto)
            ->setTotalProductsGross($totalProductsGross)
            ->setDeliveryWithInvoice($cartPackage->getDeliveryWithInvoice())
            ->setCustomerShippingForm($cartPackage->getCustomerShippingForm())
            ->setCustomerDelivery($cartPackage->getCustomerDelivery(), true)
            //->setTotalShipping($calculation->getPriceNetto())
            //->setTotalShippingGross($calculation->getPriceGross(true))
            //->setShippingTaxPercentage(round($calculation->getTaxPercentage()))
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
     */
    public function finalizeCart(
        Cart $cart,
        array $cartItemsConvertedIntoOrder = []
    ): ?Cart {
        if (!$cart) {
            $cart = $this->cartManager->getCart();
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
            ->setOrderPackage($orderPackage)
            ->setPosition($position)
            ->setCatalogPriceNet($cartPackageItem->getCartItem()->getCartItemSummary()->getBasePriceNetto())
            ->setCatalogPriceGross($cartPackageItem->getCartItem()->getCartItemSummary()->getBasePriceGross())
            ->setPriceNet($cartPackageItem->getCartItem()->getCartItemSummary()->getPriceNetto())
            ->setPriceGross($cartPackageItem->getCartItem()->getCartItemSummary()->getPriceGross())
            ->setValueNet($cartPackageItem->getCartItem()->getCartItemSummary()->getValueNetto())
            ->setValueGross($cartPackageItem->getCartItem()->getCartItemSummary()->getValueGross())
            ->setTaxRate($cartPackageItem->getCartItem()->getCartItemSummary()->getTax())
            ->setProduct($cartPackageItem->getProduct())
            ->setProductName($cartPackageItem->getProduct()?->getName())
            ->setProductNumber($cartPackageItem->getProduct()?->getNumber())
            ->setProductType($cartPackageItem->getProduct()?->getNumber())
            ->setType($cartPackageItem->getType())
            ->recalculateDiscount()
        ;

        $orderPackage->addOrderPackageItem($orderPackageItem);

        //TODO move to configuration tree builder
        if ($this->ps->get('cart.calculation.gross')) {
            $valueNetto = $this->cartManager->calculateNettoValueFromGross(
                (int) $orderPackageItem->getPriceGross(true)?->getAmount(),
                (int) $orderPackageItem->getQuantity(true)?->getAmount(),
                $orderPackageItem->getTaxRate(true)?->getFloatAmount()
            );
            $valueGross = $this->cartManager->calculateGrossValue(
                (int) $orderPackageItem->getPriceGross(true)?->getAmount(),
                (int) $orderPackageItem->getQuantity(true)?->getAmount()
            );
            TaxManager::addValueToGrossRes($orderPackageItem?->getTaxRate()->getFloatAmount(), $valueGross, $totalRes);
        } else {
            $valueNetto = $this->cartManager->calculateNettoValue(
                (int) $orderPackageItem->getPriceNet(true)->getAmount(),
                (int) $orderPackageItem->getQuantity()
            );
            $valueGross = $this->cartManager->calculateGrossValueFromNetto(
                $orderPackageItem->getPriceNet(true)?->getAmount(),
                $orderPackageItem->getQuantity(true)?->getAmount(),
                $orderPackageItem->getTaxRate(true)?->getFloatAmount()
            );
            TaxManager::addValueToNettoRes($orderPackageItem?->getTaxRate()->getFloatAmount(), $valueNetto, $totalRes);
        }

        //Saved values for
        $cartPackageItem
            ->setValueNet(ValueHelper::intToMoney($valueNetto, $cartPackageItem->getCurrencyIsoCode()))
            ->setValueGross(ValueHelper::intToMoney($valueGross, $cartPackageItem->getCurrencyIsoCode()));

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
     * TODO?
     *
     * @param Cart $cart
     * @param CartItem $cartItem
     * @return Product|null
     */
    protected function convertProductSetCartItemIntoCartItems(Cart $cart, CartItem $cartItem): ?Product
    {
        return null;
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
