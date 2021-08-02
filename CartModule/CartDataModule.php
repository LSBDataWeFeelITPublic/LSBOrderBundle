<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Module;

use LSB\CartBundle\Event\CartEvent;
use LSB\LocaleBundle\Entity\Country;
use LSB\LocaleBundle\Manager\TaxManager;
use LSB\OrderBundle\CartModule\BaseModule;
use LSB\OrderBundle\Entity\BillingData;
use LSB\OrderBundle\Entity\Cart;
use LSB\OrderBundle\Entity\CartInterface;
use LSB\OrderBundle\Entity\CartItem;
use LSB\OrderBundle\Entity\CartItemInterface;
use LSB\OrderBundle\Entity\CartPackage;
use LSB\OrderBundle\Event\CartEvents;
use LSB\OrderBundle\Model\CartSummary;
use LSB\PricelistBundle\Entity\Pricelist;
use LSB\PricelistBundle\Entity\PricelistPosition;
use LSB\PricelistBundle\Manager\PricelistManager;
use LSB\PricelistBundle\Model\Price;
use LSB\ProductBundle\Entity\Product;
use LSB\ProductBundle\Entity\ProductSetProduct;
use LSB\ProductBundle\Entity\StorageInterface;
use LSB\ProductBundle\Entity\Supplier;
use LSB\ProductBundle\Manager\StorageManager;
use LSB\ShippingBundle\Entity\Method as ShippingMethod;
use LSB\PaymentBundle\Entity\Method as PaymentMethod;
use LSB\ShippingBundle\Manager\MethodManager as ShippingMethodManager;
use LSB\UserBundle\Entity\UserInterface;
use LSB\UtilityBundle\Value\Value;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class CartDataModule
 * @package LSB\OrderBundle\Module
 */
class CartDataModule extends BaseModule
{
    const NAME = 'cartData';

    protected ShippingMethodManager $shippingMethodManager;

    protected StorageManager $storageManager;

    /**
     * @param ShippingMethodManager $shippingFormManager
     * @param PricelistManager $pricelistManager
     * @param StorageManager $storageManager
     */
    public function __construct(
        ShippingMethodManager $shippingFormManager,
        PricelistManager $pricelistManager,
        StorageManager $storageManager
    ) {
        $this->shippingMethodManager = $shippingFormManager;
        $this->storageManager = $storageManager;
    }

    /**
     * @param CartInterface|null $cart
     * @param Request $request
     */
    public function process(?CartInterface $cart, Request $request)
    {
        //ZAPIS ustawien ?
        return;
    }

    /**
     * @param CartInterface $cart
     */
    public function validateData(CartInterface $cart)
    {
        // TODO: Implement validateData() method.
    }

    /**
     * @inheritDoc
     */
    public function getResponse(CartInterface $cart)
    {

    }

    /**
     * @param CartItem $selectedCartItem
     * @return null|Price
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function getCataloguePriceForCartItem(CartItemInterface $selectedCartItem): ?Price
    {
        if (!$this->ps->get('cart.catalogue_price.calculate')) {
            return null;
        }

        /**
         * @var Cart $cart
         */
        $cart = $selectedCartItem->getCart();

        if (!$cart instanceof CartInterface) {
            throw new \Exception('Missing cart context');
        }

        $cataloguePriceTypeId = $this->cartService->getDefaultCataloguePriceType();

        if (!$cataloguePriceTypeId) {
            return null;
        }

        //ceny katalogowe zamieniamy na ceny netto z cennika detalicznego
        return $this->priceListManager->getPriceForProduct(
            $selectedCartItem->getProduct(),
            null,
            (string) $cataloguePriceTypeId,
            $cart->getCurrency(),
            $cart->getBillingContractor()
        );
    }

    /**
     * @param Cart $cart
     * @param Product $product
     * @param Product|null $productSet
     * @param float $quantity
     * @return Price|null
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function getCataloguePriceForProduct(CartInterface $cart, Product $product, ?Product $productSet, float $quantity): ?Price
    {
        if (!$this->ps->get('cart.catalogue_price.calculate')) {
            return null;
        }

        $cataloguePriceTypeId = $this->cartService->getDefaultCataloguePriceType();

        if (!$cataloguePriceTypeId) {
            return null;
        }

        //ceny katalogowe zamieniamy na ceny netto z cennika detalicznego
        return $this->priceListManager->getPriceForProduct(
            $product,
            null,
            (string) $cataloguePriceTypeId,
            $cart->getCurrency(),
            $cart->getBillingContractor()
        );
    }

    /**
     * @param Cart $cart
     * @param Product $product
     * @param Product|null $productSet
     * @param float $quantity
     * @return Price|null
     */
    protected function getPriceForProduct(Cart $cart, Product $product, ?Product $productSet, float $quantity): ?Price
    {
        return $this->priceListManager->getPriceForProduct(
            $product,
            null,
            null,
            $cart->getCurrency(),
            $cart->getBillingContractor()
        );
    }

    /**
     * @param CartItem $selectedCartItem
     * @param Price|null $cataloguePrice
     * @return array
     */
    protected function calculateCatalogueValues(CartItem $selectedCartItem, ?Price $cataloguePrice): array
    {
        if ($cataloguePrice === null) {
            return [null, null];
        }

        if ($this->ps->get('cart.calculation.gross')) {
            $catalogueValueNetto = $this->cartService->calculateNettoValueFromGross(
                $cataloguePrice->getGrossPrice(),
                $selectedCartItem->getQuantity(),
                $cataloguePrice->getVat()
            );
            $catalogueValueGross = $this->cartService->calculateGrossValue(
                $cataloguePrice->getGrossPrice(),
                $selectedCartItem->getQuantity()
            );
        } else {
            $catalogueValueNetto = $this->cartService->calculateNettoValue($cataloguePrice->getNetPrice(), $selectedCartItem->getQuantity());

            $catalogueValueGross = $this->cartService->calculateGrossValueFromNetto(
                $cataloguePrice->getNetPrice(),
                $selectedCartItem->getQuantity(),
                $cataloguePrice->getVat()
            );
        }

        return [$catalogueValueNetto, $catalogueValueGross];
    }

    /**
     * @param CartItem $selectedCartItem
     * @param Price $activePrice
     * @param array $totalRes
     * @param array $spreadRes
     * @param float|null $catalogueValueNetto
     * @param float|null $catalogueValueGross
     */
    protected function calculateActiveValues(
        CartItem $selectedCartItem,
        Price $activePrice,
        array &$totalRes,
        array &$spreadRes,
        ?float $catalogueValueNetto,
        ?float $catalogueValueGross
    ): void {
        $taxRate = $activePrice->getVat();

        if ($this->ps->getParameter('cart.calculation.gross')) {
            $valueNetto = $this->cartService->calculateNettoValueFromGross($activePrice->getGrossPrice(), $selectedCartItem->getQuantity(), $activePrice->getVat());
            $valueGross = $this->cartService->calculateGrossValue($activePrice->getGrossPrice(), $selectedCartItem->getQuantity());

            TaxManager::addValueToGrossRes($taxRate, $valueGross, $totalRes);
            if ($catalogueValueGross !== null) {
                TaxManager::addValueToGrossRes($taxRate, ($catalogueValueGross > $valueGross) ? $catalogueValueGross - $valueGross : 0, $spreadRes);
            }
        } else {
            $valueNetto = $this->cartService->calculateNettoValue($activePrice->getNetPrice(), $selectedCartItem->getQuantity());
            $valueGross = $this->cartService->calculateGrossValueFromNetto($activePrice->getNetPrice(), $selectedCartItem->getQuantity(), $activePrice->getVat());

            TaxManager::addValueToNettoRes($taxRate, $valueNetto, $totalRes);
            if ($catalogueValueNetto !== null) {
                TaxManager::addValueToNettoRes($taxRate, ($catalogueValueNetto > $valueNetto) ? $catalogueValueNetto - $valueNetto : 0, $spreadRes);
            }
        }
    }

    /**
     * @param Product $product
     * @param float $quantity
     * @param Price $activePrice
     * @param array $totalRes
     * @param array $spreadRes
     * @param float|null $catalogueValueNetto
     * @param float|null $catalogueValueGross
     */
    protected function calculateActiveValuesWithProduct(
        Product $product,
        Value $quantity,
        Price $activePrice,
        array &$totalRes,
        array &$spreadRes,
        ?float $catalogueValueNetto,
        ?float $catalogueValueGross
    ): void {
        $taxRate = $activePrice->getVat();

        if ($this->ps->getParameter('cart.calculation.gross')) {
            $valueNetto = $this->cartService->calculateNettoValueFromGross($activePrice->getGrossPrice(), (int) $quantity->getAmount(), $activePrice->getVat());
            $valueGross = $this->cartService->calculateGrossValue($activePrice->getGrossPrice(), (int) $quantity->getAmount());

            TaxManager::addValueToGrossRes($taxRate, $valueGross, $totalRes);
            if ($catalogueValueGross !== null) {
                TaxManager::addValueToGrossRes($taxRate, ($catalogueValueGross > $valueGross) ? $catalogueValueGross - $valueGross : 0, $spreadRes);
            }
        } else {
            $valueNetto = $this->cartService->calculateNettoValue($activePrice->getNetPrice(), (int) $quantity->getAmount());
            $valueGross = $this->cartService->calculateGrossValueFromNetto($activePrice->getNetPrice(), (int) $quantity->getAmount(), $activePrice->getVat());

            TaxManager::addValueToNettoRes($taxRate, $valueNetto, $totalRes);
            if ($catalogueValueNetto !== null) {
                TaxManager::addValueToNettoRes($taxRate, ($catalogueValueNetto > $valueNetto) ? $catalogueValueNetto - $valueNetto : 0, $spreadRes);
            }
        }
    }

    /**
     * @param CartItem $selectedCartItem
     * @return Price
     * @throws \Exception
     */
    protected function getActivePriceForCartItem(CartItemInterface $selectedCartItem): Price
    {
        $price = $this->cartService->getPriceForCartItem($selectedCartItem);

        if (!$price instanceof Price) {
            throw new \Exception('Missing price object');
        }

        return $price;
    }


    /**
     * @param Cart $cart
     * @param bool $rebuildPackages
     * @return CartSummary
     * @throws \Exception
     */
    public function getCartSummary(Cart $cart, bool $rebuildPackages = false): CartSummary
    {
        //Aktualizujemy zawartość koszyka
        if ($rebuildPackages) {
            $this->cartService->rebuildCart($cart, true, false);
        } elseif ($cart->getCartSummary() && $cart->getCartSummary()->getCalculatedAt() !== null) {
            return $cart->getCartSummary();
        }

        $selectedCartItems = $cart->getSelectedItems();

        $totalRes = [];
        $spreadRes = [];
        $shippingCostRes = [];

        $totalNetto = (float)0.00;
        $totalGross = (float)0.00;

        $spreadNetto = (float)0.00;
        $spreadGross = (float)0.00;

        //Sumy poszczególnych wartości pozycji - nie używać do wyliczania wartości total netto i brutto całego koszyka - tylko do prezentacji
        $totalItemsNetto = (float)0.00;
        $totalItemsGross = (float)0.00;

        $cnt = $cart->getCartItems()->count();
        $cntSelected = $cart->countSelectedItems();

        /**
         * @var CartItem $selectedCartItem
         */
        foreach ($selectedCartItems as $selectedCartItem) {


            if ($selectedCartItem->getProduct() && $selectedCartItem->getProduct()->isProductSet()) {
                //Mamy do czynienia z zestawem

                /**
                 * @var ProductSetProduct $productSetProduct
                 */
                foreach ($selectedCartItem->getProduct()->getProductSetProducts() as $productSetProduct) {
                    $product = $productSetProduct->getProduct();
                    $productQuantity = $productSetProduct->getQuantity();
                    $productSet = $selectedCartItem->getProduct();
                    $calculatedQuantity = $selectedCartItem->getQuantity() * $productQuantity;

                    $cataloguePrice = $this->getCataloguePriceForProduct($selectedCartItem->getCart(), $product, $productSet, $calculatedQuantity);
                    [$catalogueValueNetto, $catalogueValueGross] = $this->calculateCatalogueValues($selectedCartItem, $cataloguePrice);


                    if ($selectedCartItem->getCartItemSummary()
                        && $selectedCartItem->getCartItemSummary()->isProductSet()
                        && $selectedCartItem->getCartItemSummary()->getCalculatedAt()
                        && $selectedCartItem->getCartItemSummary()->hasProductSetProductActivePriceByProductId($product->getId())
                    ) {
                        $productActivePrice = $selectedCartItem->getCartItemSummary()->getProductSetProductActivePriceByProductId($product->getId());
                    } else {
                        $productActivePrice = $this->getPriceForProduct($selectedCartItem->getCart(), $product, $productSet, $calculatedQuantity);
                    }

                    $this->calculateActiveValuesWithProduct(
                        $product,
                        $calculatedQuantity,
                        $productActivePrice,
                        $totalRes,
                        $spreadRes,
                        $catalogueValueNetto,
                        $catalogueValueGross
                    );
                }

            } else {
                //Zwykły produkt

                $cataloguePrice = $this->getCataloguePriceForCartItem($selectedCartItem);
                [$catalogueValueNetto, $catalogueValueGross] = $this->calculateCatalogueValues($selectedCartItem, $cataloguePrice);

                if ($selectedCartItem->getCartItemSummary()
                    && $selectedCartItem->getCartItemSummary()->getCalculatedAt()
                    && $selectedCartItem->getCartItemSummary()->getActivePrice()
                ) {
                    $activePrice = $selectedCartItem->getCartItemSummary()->getActivePrice();
                } else {
                    $activePrice = $this->getActivePriceForCartItem($selectedCartItem);
                }

                $this->calculateActiveValues(
                    $selectedCartItem,
                    $activePrice,
                    $totalRes,
                    $spreadRes,
                    $catalogueValueNetto,
                    $catalogueValueGross
                );
            }
            

        }

        //zaokrąglamy na samym końcu
        if ($this->ps->get('cart.calculation.gross')) {
            [$totalProductsNetto, $totalProductsGross] = TaxManager::calculateTotalNettoAndGrossFromGrossRes($totalRes, $this->cartService->addTax($cart));
        } else {
            [$totalProductsNetto, $totalProductsGross] = TaxManager::calculateTotalNettoAndGrossFromNettoRes($totalRes, $this->cartService->addTax($cart));
        }

        [
            $shippingTotalNetto,
            $shippingTotalGrossRounded,
            $shippingTotalGross,
            $shippingTaxPercentage,
            $shippingCostRes,
            $freeDeliveryThresholdNetto,
            $freeDeliveryThresholdGross
        ] = $this->calculateShippingCost(
            $cart,
            $this->cartService->addTax($cart),
            $this->ps->get('cart.calculation.gross') ? $totalProductsGross : $totalProductsNetto,
            $shippingCostRes
        );

        [$paymentCostNetto, $paymentCostGross] = $this->calculatePaymentCost($cart, $this->cartService->addTax($cart), $totalRes);

        if ($this->ps->get('cart.calculation.gross')) {
            [$shippingCostNetto, $shippingCostGross] = TaxManager::calculateTotalNettoAndGrossFromGrossRes($shippingCostRes, $this->cartService->addTax($cart));
        } else {
            [$shippingCostNetto, $shippingCostGross] = TaxManager::calculateTotalNettoAndGrossFromNettoRes($shippingCostRes, $this->cartService->addTax($cart));
        }

        //sumaryczna wartość produktów z kosztami dostawy
        $totalWithShippingNettoRes = TaxManager::mergeRes($shippingCostRes, $totalRes);

        if ($this->ps->get('cart.calculation.gross')) {
            [$totalWithShippingNetto, $totalWithShippingGross] = TaxManager::calculateTotalNettoAndGrossFromGrossRes(
                $totalWithShippingNettoRes,
                $this->cartService->addTax($cart)
            );
        } else {
            [$totalWithShippingNetto, $totalWithShippingGross] = TaxManager::calculateTotalNettoAndGrossFromNettoRes(
                $totalWithShippingNettoRes,
                $this->cartService->addTax($cart)
            );
        }

        [$shippingCostFromNetto, $shippingCostFromGross] = $this->getShippingCostFrom($cart, $shippingCostNetto);

        $cartSummary = (new CartSummary)
            ->setCnt($cnt)
            ->setSelectedCnt($cntSelected)
            ->setTotalProductsNetto($totalProductsNetto)
            ->setTotalProductsGross($totalProductsGross)
            ->setShippingCostNetto($shippingCostNetto)
            ->setShippingCostGross($shippingCostGross)
            ->setPaymentCostNetto($paymentCostNetto)
            ->setPaymentCostGross($paymentCostGross)
            ->setTotalNetto($totalWithShippingNetto)
            ->setTotalGross($totalWithShippingGross)
            ->setSpreadNetto($spreadNetto)
            ->setSpreadGross($spreadGross)
            ->setCalculatedAt(new \DateTime('NOW'))
            ->setShowVatViesWarning($this->showVatViesWarning($cart))
            ->setFreeShippingThresholdNetto($freeDeliveryThresholdNetto)
            ->setFreeShippingThresholdGross($freeDeliveryThresholdGross)
            ->setShippingCostFromNetto($shippingCostFromNetto)
            ->setShippingCostFromGross($shippingCostFromGross)
            ->setCalculationType($this->ps->get('cart.calculation.gross') ? CartSummary::CALCULATION_TYPE_GROSS : CartSummary::CALCULATION_TYPE_NET)
            ->setCurrencyCode((string)$cart->getCurrency());

        $cart->setCartSummary($cartSummary);

        $cart
            ->setTotalValueGross($cartSummary->getTotalGross())
            ->setTotalValueNet($cartSummary->getTotalNetto())
        ;

        $this->eventDispatcher->dispatch(new CartEvent($cart), CartEvents::CART_SUMMARY_CALCULATED);

        return $cartSummary;
    }

    /**
     * Metoda zwraca minimalny koszt dostawy dostępny dla klienta
     *
     * @param Cart $cart
     * @param float|null $shippingCostNetto ???
     * @return array
     * @throws \Exception
     */
    protected function getShippingCostFrom(CartInterface $cart, ?float $shippingCostNetto = 0): array
    {
        return [];
    }

    /**
     * @param Cart $cart
     * @return bool
     * @throws \Exception
     */
    public function showVatViesWarning(Cart $cart): bool
    {
        if ($cart->getBillingContractorCountry()
            && $this->taxManager->getSellerCountry() !== $cart->getBillingContractorCountry()
            && $cart->getBillingContractorCountry() && $cart->getBillingContractorCountry()->isUeMember()
            && ($cart->getBillingContractorData()->getType() == BillingData::TYPE_COMPANY
                || $cart->getBillingContractorData()->getType() === null) && !$cart->getBillingContractorData()->getIsVatUEActivePayer()
        ) {
            return true;
        }

        return false;
    }

    /**
     * @param Cart|null $cart
     * @param bool $addVat
     * @param null $calculatedTotalProducts
     * @param $shippingCostRes
     * @return array
     * @throws \Exception
     */
    public function calculateShippingCost(
        Cart $cart = null,
        bool $addVat,
        $calculatedTotalProducts,
        &$shippingCostRes
    ): array {
        if (!$cart) {
            $cart = $this->cartService->getCart();
        }

        $packages = $cart->getCartPackages();

        $totalNetto = 0;
        $totalGrossRounded = 0;
        $totalGross = 0;
        $taxPercentage = null;
        $freeDeliveryThresholdNetto = null;
        $freeDeliveryThresholdGross = null;

        //TODO
//        /**
//         * @var CartPackage $package
//         */
//        foreach ($packages as $package) {
//            $calculation = $this->cartService->calculatePackageShippingCost(
//                $package,
//                $addVat,
//                $calculatedTotalProducts,
//                $shippingCostRes
//            );
//
//            $totalNetto += $calculation->getPriceNetto();
//            $totalGross += $calculation->getPriceGross();
//            $totalGrossRounded += $calculation->getPriceGross(true);
//            $freeDeliveryThresholdNetto = $calculation->getFreeDeliveryThresholdValueNetto();
//            $freeDeliveryThresholdGross = $calculation->getFreeDeliveryThresholdValueGross();
//        }

        return [
            $totalNetto,
            $totalGrossRounded,
            $totalGross,
            $taxPercentage,
            $shippingCostRes,
            $freeDeliveryThresholdNetto,
            $freeDeliveryThresholdGross
        ];
    }

    /**
     * Wyliczanie kosztów płatności
     *
     * @param Cart|null $cart
     * @param $addVat
     * @param $totalRes
     * @return array
     * @throws \Exception
     */
    protected function calculatePaymentCost(Cart $cart = null, $addVat, &$totalRes)
    {
        $totalNetto = 0;
        $totalGross = 0;

        $paymentMethod = $cart->getPaymentMethod();

        if ($paymentMethod instanceof PaymentMethod && $paymentMethod->getPrice()) {

            //TODO Fetching payment cost prices from priclists

            $paymentCostNetto = 0;
            $paymentCostGross = 0;
            $taxRate = 23;

            $totalNetto += $paymentCostNetto;
            $totalGross += $paymentCostGross;

            //Uzupełniamy tablicę netto o koszty dostawy
            TaxManager::addValueToNettoRes(
                $taxRate,
                $this->ps->get('cart.calculation.gross') ? $paymentCostGross : $paymentCostNetto,
                $totalRes
            );
        }

        return [$totalNetto, $totalGross];
    }

    /**
     * @param Cart $cart
     * @throws \Exception
     */
    public function prepare(CartInterface $cart): mixed
    {
        parent::prepare($cart);

        $user = $this->getUser();

        if ($user && $user->getDefaultBillingContractor() && $cart->getBillingContractorData()->getEmail() === null) {
            $cart->getBillingContractorData()->setEmail($user->getEmail());
        }

        return;
    }

    /**
     * @inheritdoc
     */
    public function render(CartInterface $cart, ?Request $request = null, bool $isInitialRender = false)
    {
        return null;
    }

    /**
     * @param Cart $cart
     * @param UserInterface|null $user
     */
    public function convertSessionCartToUserCart(Cart $cart, UserInterface $user = null)
    {
        $customer = $user->getDefaultBillingContractor();

        $cart
            ->setAuthType(CartInterface::AUTH_TYPE_USER)
            ->setUser($user)
            ->setBillingContractor($customer)
            ->setIsConvertedFromSession(true)
            ->setSessionId(null)
            ->setValidatedStep(null);

        //TODO
        //CartPackage validation is reuqired at this moment
//        $packageShippingModule = $this->moduleManager->getModuleByName(BasePackageShippingModule::NAME);
//        if ($packageShippingModule) {
//            $packageShippingModule->validate($cart);
//        }

        //Payment validation is required at this moment
//        $paymentModule = $this->moduleManager->getModuleByName(BasePaymentModule::NAME);
//        if ($paymentModule) {
//            $paymentModule->validate($cart);
//        }

        $this->eventDispatcher->dispatch(
            new CartEvent($cart),
            CartEvents::CART_SESSION_CONVERTED_TO_USER,
        );

        $this->em->flush();
    }

    /**
     * Metoda scalająca pozycje w koszyku, wymusza aktualizacją paczek
     *
     * @param Cart $sessionCart
     * @param Cart $storedCart
     * @return Cart
     * @throws \Exception
     */
    public function mergeCarts(Cart $sessionCart, Cart $storedCart): Cart
    {
        //w takiej sytuacji zakładamy, że koszyk sesyjny zostanie zamknięty

        //budujemy tablicę aktualizacji
        $updateData = [];

        /**
         * @var CartItem $sessionCartItem
         */
        foreach ($sessionCart->getCartItems() as $sessionCartItem) {
            if (!$sessionCartItem->getProduct()) {
                continue;
            }
            //TODO zamienić na value objects
            $updateData[] = [
                'uuid' => $sessionCartItem->getProduct()->getUuid(),
                'quantity' => $sessionCartItem->getQuantity(),
                'ordercode' => $sessionCartItem->getOrdercode(),
            ];
        }


        /**
         * @var CartItemsModule $cartItemsModule
         */
//        $cartItemsModule = $this->moduleManager->getModuleByName(BaseCartItemsModule::NAME);
//
//        $processedItemsData = $cartItemsModule->rebuildAndProcessCartItems($storedCart);
//
//        if (count($updateData) > 0) {
//            //rozpoczynamy proces scalania
//            $cartItemsModule->updateCartItems($storedCart, $updateData, true, true, false, true);
//        }

        //Zamykamy koszyk sesyjny
        $sessionCartValidatedStep = $sessionCart->getValidatedStep();
        $sessionCart->setValidatedStep(Cart::CART_STEP_CLOSED_BY_MERGE);

        $this->eventDispatcher->dispatch(
            new CartEvent(
                $sessionCart
            ),
            CartEvents::CART_CLOSED
        );

        //Zapisujemy koszyk permanentny
        $storedCart
            ->setValidatedStep(null)
            //->clearPackagesDefaultDeliveryData()
            ->getBillingContractorData()->setIsVatUEActivePayer(null);
        $storedCart
            ->setIsMerged(true)
            ->setNote($sessionCart->getNote() ? $storedCart->getNote() . "\n" . $sessionCart->getNote() : $storedCart->getNote())
            ->setInvoiceNote($sessionCart->getInvoiceNote() ? $storedCart->getInvoiceNote() . "\n" . $sessionCart->getInvoiceNote() : $storedCart->getInvoiceNote())
            ->setOrderVerificationNote($sessionCart->getOrderVerificationNote() ? $storedCart->getOrderVerificationNote() . "\n" . $sessionCart->getOrderVerificationNote() : $storedCart->getOrderVerificationNote());

        if ($sessionCart->isOrderVerificationRequested()) {
            $storedCart->setIsOrderVerificationRequested($sessionCart->isOrderVerificationRequested());
        }

        if ($sessionCart->getClientOrderNumber()) {
            $storedCart->setClientOrderNumber($sessionCart->getClientOrderNumber());
        }

        if ($sessionCart->getPaymentMethod()) {
            $storedCart->setPaymentMethod($sessionCart->getPaymentMethod());
        }

        if ($sessionCart->getAuthType()) {
            //Aktualnie zawsze ustawiony zostanie type USER
            $storedCart->setAuthType($sessionCart->getAuthType());
        }

        if ($sessionCart->getBillingContractorCountry()) {
            $storedCart->setBillingContractorCountry($sessionCart->getBillingContractorCountry());
        }

        //Przepisanie ustawień paczek
        $sessionPackages = [];

        /**
         * @var CartPackage $sessionPackage
         */
        foreach ($sessionCart->getCartPackages() as $key => $sessionPackage) {
            $sessionPackages[$key] = $sessionPackage;
        }

        /**
         * @var CartPackage $storedPackage
         */
        foreach ($storedCart->getCartPackages() as $key => $storedPackage) {
            if (array_key_exists($key, $sessionPackages)) {
                $storedPackage
                    ->setDeliveryNote($sessionPackages[$key]->getDeliveryNotes() ? $storedPackage->getDeliveryNote() . "\n" . $sessionPackages[$key]->getDeliveryNotes() : $storedPackage->getDeliveryNote());

                if ($sessionPackages[$key]->getShippingMethod()) {
                    $storedPackage->setShippingMethod($sessionPackages[$key]->getShippingMethod());
                }
            }
        }

        $this->eventDispatcher->dispatch(
            new CartEvent(
                $storedCart
            ),
            CartEvents::CART_MERGED
        );

        $this->em->persist($storedCart);
        $this->em->flush();

        return $storedCart;
    }

    /**
     * @param Product|null $product
     * @return float
     */
    protected function getRawLocalQuantityForProduct(?Product $product): int
    {
        return (int)($product ? $product->getShopQuantityAvailableAtHandFromLocalStorages() : 0);
    }

    /**
     * @param Product|null $product
     * @param int|null $userQuantity
     * @return int
     */
    protected function getRawRemoteQuantityForProduct(?Product $product, ?int $userQuantity = null): int
    {
        //Uwaga, aktualnie nie ma możliwości ustalenia zdalnego stanu magazynowego dostawcy dlatego pozwalamy na zamówienie każdej ilości w przypadku dostawcy innego niż domyślny
        if ($userQuantity !== null && $userQuantity > 0 && $product->getUseSupplier() && $product->getSupplier() instanceof Supplier) {
            return $userQuantity;
        }

        return (int)($product ? $product->getShopQuantityAvailableAtHandFromRemoteStorages() : 0);
    }

    /**
     * Podstawowa metoda wyliczająca dostępne stany mag, wylicza dostępną ilość do zamówienia, zachowując rozdzial pomiędzy dostępnością lokalną i zdalną
     * Do użycia w przebudowaie paczek lokalnych, backorder, wyliczania dostępnego stanu całkowitego
     *
     * @param Product $product
     * @param float $userQuantity
     * @return array
     * @throws \Exception
     */
    public function calculateQuantityForProduct(Product $product, float $userQuantity)
    {
        $localQuantity = $this->getRawLocalQuantityForProduct($product);

        //Rezerwacja stanu lokalnego
        $localQuantity = $this->storageManager->checkReservedQuantity(
            $product->getId(),
            $userQuantity,
            Storage::TYPE_LOCAL,
            $localQuantity
        );

        $requestedRemoteQuantity = ($userQuantity - $localQuantity > 0) ? $userQuantity - $localQuantity : 0;

        //Niezależnie od ustawienia ordercode, na tym etapi nie zezwalamy na rezerwację stanów
        [$remoteQuantity, $remoteStoragesWithShippingDays, $backOrderPackageQuantity, $remoteStoragesCountBeforeMerge] = $this->storageManager->calculateRemoteShippingQuantityAndDays(
            $product,
            $requestedRemoteQuantity,
            false,
            true,
            false
        );

        $localShippingDays = $product->getShippingDays($this->ps->getParameter('localstorage_number'));
        $remoteShippingDaysList = array_keys($remoteStoragesWithShippingDays);
        $remoteShippingDays = end($remoteShippingDaysList);

        $maxShippingDaysForUserQuantity = ($remoteShippingDays > $localShippingDays) ? $remoteShippingDays : $localShippingDays;

        $futureQuantity = 0;
        $localPackageQuantity = $remotePackageQuantity = 0.0;

        if ($userQuantity <= $localQuantity) {
            $localPackageQuantity = $userQuantity;
        } elseif ($userQuantity > $localQuantity && $userQuantity <= ($localQuantity + $remoteQuantity + $futureQuantity + $backOrderPackageQuantity)) {
            $localPackageQuantity = (float)$localQuantity;
            $remotePackageQuantity = (float)$remoteQuantity;
        } else {
            //liczba sztuk przewyższa zapasy magazynowe - w tym miejscu jest to niedopuszczalne
            throw new \Exception('Wrong quantity in packages');
        }

        return [
            (float)$localPackageQuantity, //ilość dostępna dla paczki lokalnej
            (float)$remotePackageQuantity, //ilość dostępna dla paczki zdalnej
            (float)$backOrderPackageQuantity, //ilość dostępna dla paczki na zamówienie
            (int)$localShippingDays, //czas dostawy lokalnej
            (int)$remoteShippingDays, //czas dostawy zdalnej
            $remoteStoragesWithShippingDays, //magazyny zdalne z czasem dostawy
            (int)$maxShippingDaysForUserQuantity, //maksymalny czas dostawy dla
            (float)$localQuantity, //stan lokalny
            (float)$remoteQuantity, //stan zdalny
            $remoteStoragesCountBeforeMerge, //stany zdalne przed scaleniem
        ];
    }

    /**
     * Metoda przeznaczona do obsługi stanów magazynowych w oparciu o działający trigger przeliczania dostępnych wartości.
     *
     * @param Product $product
     * @param float $userQuantity
     * @return array
     * @throws \Exception
     */
    public function getCalculatedQuantityForProduct(Product $product, int $userQuantity)
    {
        $localRawQuantity = $this->getRawLocalQuantityForProduct($product);
        $remoteRawQuantity = $this->getRawRemoteQuantityForProduct($product, $userQuantity);

        //Rezerwacja stanu lokalnego
        $localQuantity = $this->storageManager->checkReservedQuantity(
            $product->getId(),
            $userQuantity,
            StorageInterface::TYPE_LOCAL,
            $localRawQuantity
        );

        $requestedRemoteQuantity = ($userQuantity - $localQuantity > 0) ? $userQuantity - $localQuantity : 0;

        $remoteQuantity = $this->storageManager->checkReservedQuantity(
            $product->getId(),
            $requestedRemoteQuantity,
            StorageInterface::TYPE_EXTERNAL,
            $remoteRawQuantity
        );

        //Uwaga, aktualnie nie ma możliwości ustalenia zdalnego stanu magazynowego dostawcy dlatego pozwalamy na zamówienie każdej ilości w przypadku dostawcy zewnętrznego
        if ($requestedRemoteQuantity > 0
            && $product->getUseSupplier()
            && $product->getSupplier() instanceof Supplier
        ) {
            $remoteQuantity = $requestedRemoteQuantity;
        }

        $backOrderPackageQuantity = ($userQuantity > $localQuantity + $remoteQuantity) && $this->cartService->isBackorderEnabled() ? $userQuantity - $localQuantity - $remoteQuantity : 0;

        $localShippingDays = $product->getShippingDays($this->ps->getParameter('localstorage_number'));
        $remoteShippingDaysList = [Storage::DEFAULT_DELIVERY_TERM];
        $remoteShippingDays = Storage::DEFAULT_DELIVERY_TERM;

        $maxShippingDaysForUserQuantity = ($remoteShippingDays > $localShippingDays) ? $remoteShippingDays : $localShippingDays;

        $futureQuantity = 0;
        $localPackageQuantity = $remotePackageQuantity = 0.0;

        if ($userQuantity <= $localQuantity) {
            $localPackageQuantity = $userQuantity;
        } elseif ($userQuantity > $localQuantity && $userQuantity <= ($localQuantity + $remoteQuantity + $futureQuantity + $backOrderPackageQuantity)) {
            $localPackageQuantity = (float)$localQuantity;
            $remotePackageQuantity = (float)$remoteQuantity;
        } else {
            //liczba sztuk przewyższa zapasy magazynowe - w tym miejscu jest to niedopuszczalne
            throw new WrongPackageQuantityException('Wrong quantity in packages');
        }

        return [
            (float)$localPackageQuantity, //ilość dostępna dla paczki lokalnej
            (float)$remotePackageQuantity, //ilość dostępna dla paczki zdalnej
            (float)$backOrderPackageQuantity, //ilość dostępna dla paczki na zamówienie
            (int)$localShippingDays, //czas dostawy lokalnej
            (int)$remoteShippingDays, //czas dostawy zdalnej
            $remoteStoragesWithShippingDays = [], //magazyny zdalne z czasem dostawy
            (int)$maxShippingDaysForUserQuantity, //maksymalny czas dostawy dla
            (float)$localQuantity, //stan lokalny
            (float)$remoteQuantity, //stan zdalny
            $remoteStoragesCountBeforeMerge = [], //stany zdalne przed scaleniem
        ];
    }

    /**
     * Metoda wylicza stany uwzględniając stany lokalne w ramach dostępności zdalnej (wówczas stan lokalny może zostać scalony ze stanem zdalnym)
     *
     * @param Product $product
     * @param float $userQuantity
     * @return array
     * @throws \Exception
     */
    protected function calculateQuantityForProductWithLocalMerge(Product $product, float $userQuantity)
    {
        [
            $calculatedQuantity,
            $storagesWithShippingDays,
            $backorderPackageQuantity,
            $storagesCountBeforeMerge
        ] = $this->storageManager->calculateRemoteShippingQuantityAndDays(
            $product->getDetailsByClass(EdiProductDetails::class), //TODO Dodać wsparcie dla pobierania klasy z kontekstu aplikacji
            $userQuantity,
            $useLocalStorageAsRemote = true,
            true,//$this->ps->getParameter('cart.ordercode.enabled') ? true : false,
            false
        );

        $shippingDaysList = array_keys($storagesWithShippingDays);
        $shippingDays = end($shippingDaysList);

        return [
            (float)$calculatedQuantity,
            (float)$backorderPackageQuantity,
            (int)$shippingDays,
            $storagesWithShippingDays,
            $storagesCountBeforeMerge,
        ];
    }

    /**
     * @inheritDoc
     */
    public function getConfiguration(
        Cart $cart,
        ?UserBuyerInterface $user = null,
        ?Customer $customer = null,
        ?Request $request = null,
        bool $isInitialRender = false
    ): CartModuleConfiguration {
        return new CartModuleConfiguration(false, false, []);
    }
}
