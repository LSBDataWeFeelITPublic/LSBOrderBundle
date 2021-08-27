<?php
declare(strict_types=1);

namespace LSB\OrderBundle\CartComponent;

use JMS\Serializer\SerializerInterface;
use LSB\ContractorBundle\Entity\ContractorInterface;
use LSB\LocaleBundle\Entity\CurrencyInterface;
use LSB\LocaleBundle\Manager\TaxManager;
use LSB\OrderBundle\Entity\BillingData;
use LSB\OrderBundle\Entity\Cart;
use LSB\OrderBundle\Entity\CartInterface;
use LSB\OrderBundle\Entity\CartItem;
use LSB\OrderBundle\Entity\CartItemInterface;
use LSB\OrderBundle\Entity\CartPackage;
use LSB\OrderBundle\Event\CartEvent;
use LSB\OrderBundle\Event\CartEvents;
use LSB\OrderBundle\Exception\WrongPackageQuantityException;
use LSB\OrderBundle\Manager\CartItemManager;
use LSB\OrderBundle\Manager\CartManager;
use LSB\OrderBundle\Model\CartSummary;
use LSB\OrderBundle\Service\CartService;
use LSB\PaymentBundle\Entity\Method as PaymentMethod;
use LSB\PricelistBundle\Manager\PricelistManager;
use LSB\PricelistBundle\Model\Price;
use LSB\ProductBundle\Entity\Product;
use LSB\ProductBundle\Entity\ProductSetProduct;
use LSB\ProductBundle\Entity\StorageInterface;
use LSB\ProductBundle\Entity\Supplier;
use LSB\ProductBundle\Manager\ProductManager;
use LSB\ProductBundle\Manager\ProductSetProductManager;
use LSB\ProductBundle\Manager\StorageManager;
use LSB\ProductBundle\Service\StorageService;
use LSB\ShippingBundle\Manager\MethodManager as ShippingMethodManager;
use LSB\UserBundle\Entity\User;
use LSB\UserBundle\Entity\UserInterface;
use LSB\UserBundle\Manager\UserManager;
use LSB\UtilityBundle\Helper\ValueHelper;
use LSB\UtilityBundle\Value\Value;
use Money\Currency as MoneyCurrency;
use Money\Money;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/**
 *
 */
class DataCartComponent extends BaseCartComponent
{
    const NAME = 'data';

    protected CartInterface|null $cart = null;

    public function __construct(
        TokenStorageInterface                   $tokenStorage,
        protected ParameterBagInterface         $ps,
        protected CartManager                   $cartManager,
        protected CartItemManager               $cartItemManager,
        protected ShippingMethodManager         $shippingFormManager,
        protected pricelistManager              $pricelistManager,
        protected StorageManager                $storageManager,
        protected StorageService                $storageService,
        protected RequestStack                  $requestStack,
        protected EventDispatcherInterface      $eventDispatcher,
        protected TaxManager                    $taxManager,
        protected FormFactory                   $formFactory,
        protected UserManager                   $userManager,
        protected SerializerInterface           $serializer,
        protected AuthorizationCheckerInterface $authorizationChecker,
        protected Environment                   $templating,
        protected TranslatorInterface           $translator,
        protected ProductManager                $productManager,
        protected ProductSetProductManager      $productSetProductManager
    ) {
        parent::__construct($tokenStorage);
    }

    /**
     * @return ParameterBagInterface
     */
    public function getPs(): ParameterBagInterface
    {
        return $this->ps;
    }

    /**
     * @return ShippingMethodManager
     */
    public function getShippingFormManager(): ShippingMethodManager
    {
        return $this->shippingFormManager;
    }

    /**
     * @return PricelistManager
     */
    public function getPricelistManager(): PricelistManager
    {
        return $this->pricelistManager;
    }

    /**
     * @return StorageManager
     */
    public function getStorageManager(): StorageManager
    {
        return $this->storageManager;
    }

    /**
     * @return StorageService
     */
    public function getStorageService(): StorageService
    {
        return $this->storageService;
    }

    /**
     * @return RequestStack
     */
    public function getRequestStack(): RequestStack
    {
        return $this->requestStack;
    }

    /**
     * @return EventDispatcherInterface
     */
    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    /**
     * @return TaxManager
     */
    public function getTaxManager(): TaxManager
    {
        return $this->taxManager;
    }

    /**
     * @return FormFactory
     */
    public function getFormFactory(): FormFactory
    {
        return $this->formFactory;
    }

    /**
     * @return UserManager
     */
    public function getUserManager(): UserManager
    {
        return $this->userManager;
    }

    /**
     * @return SerializerInterface
     */
    public function getSerializer(): SerializerInterface
    {
        return $this->serializer;
    }

    /**
     * @return AuthorizationCheckerInterface
     */
    public function getAuthorizationChecker(): AuthorizationCheckerInterface
    {
        return $this->authorizationChecker;
    }

    /**
     * @return Environment
     */
    public function getTemplating(): Environment
    {
        return $this->templating;
    }

    /**
     * @return CartManager
     */
    public function getCartManager(): CartManager
    {
        return $this->cartManager;
    }

    /**
     * @return CartItemManager
     */
    public function getCartItemManager(): CartItemManager
    {
        return $this->cartItemManager;
    }

    /**
     * @return TranslatorInterface
     */
    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    /**
     * @return ProductManager
     */
    public function getProductManager(): ProductManager
    {
        return $this->productManager;
    }

    /**
     * @return ProductSetProductManager
     */
    public function getProductSetProductManager(): ProductSetProductManager
    {
        return $this->productSetProductManager;
    }

    /**
     * TODO
     * @return mixed
     * @deprecated Moved to CartDataService
     * #
     */
    public function getDefaultDetalPriceType()
    {
        return null;
    }

    /**
     * TODO
     * @param bool $getId
     * @return int|null
     * @deprecated Moved to CartDataService
     */
    public function getDefaultCataloguePriceType(bool $getId = false)
    {
        return null;
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

        $cataloguePriceTypeId = $this->getDefaultCataloguePriceType();

        if (!$cataloguePriceTypeId) {
            return null;
        }

        //ceny katalogowe zamieniamy na ceny netto z cennika detalicznego
        return $this->pricelistManager->getPriceForProduct(
            $selectedCartItem->getProduct(),
            null,
            (string)$cataloguePriceTypeId,
            $cart->getCurrency(),
            $cart->getBillingContractor()
        );
    }

    /**
     * @param Cart $cart
     * @param Product $product
     * @param Product|null $productSet
     * @param Value $quantity
     * @return Price|null
     * @throws \Exception
     */
    protected function getCataloguePriceForProduct(
        CartInterface $cart,
        Product $product,
        ?Product $productSet,
        Value $quantity
    ): ?Price {
        if (!$this->ps->get('cart.catalogue_price.calculate')) {
            return null;
        }

        $cataloguePriceTypeId = $this->getDefaultCataloguePriceType();

        if (!$cataloguePriceTypeId) {
            return null;
        }

        //ceny katalogowe zamieniamy na ceny netto z cennika detalicznego
        return $this->pricelistManager->getPriceForProduct(
            $product,
            null,
            (string)$cataloguePriceTypeId,
            $cart->getCurrency(),
            $cart->getBillingContractor()
        );
    }

    /**
     * @param Cart $cart
     * @param Product $product
     * @param Product|null $productSet
     * @param Value $quantity
     * @return Price|null
     * @throws \Exception
     */
    protected function getPriceForProduct(
        Cart $cart,
        Product $product,
        ?Product $productSet,
        Value $quantity
    ): ?Price {
        return $this->pricelistManager->getPriceForProduct(
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
     * @throws \Exception
     */
    protected function calculateCatalogueValues(CartItem $selectedCartItem, ?Price $cataloguePrice): array
    {
        if ($cataloguePrice === null) {
            return [null, null];
        }

        if ($this->ps->get('cart.calculation.gross')) {
            $catalogueValueNetto = $this->calculateMoneyNetValueFromGrossPrice(
                $cataloguePrice->getGrossPrice(true),
                $selectedCartItem->getQuantity(true),
                $cataloguePrice->getVat(true)
            );
            $catalogueValueGross = $this->calculateMoneyGrossValue(
                $cataloguePrice->getGrossPrice(true),
                $selectedCartItem->getQuantity(true)
            );
        } else {
            $catalogueValueNetto = $this->calculateMOneyNetValue(
                $cataloguePrice->getNetPrice(true),
                $selectedCartItem->getQuantity(true)
            );

            $catalogueValueGross = $this->calculateMOneyGrossValueFromNetPrice(
                $cataloguePrice->getNetPrice(true),
                $selectedCartItem->getQuantity(),
                $cataloguePrice->getVat()
            );
        }

        return [$catalogueValueNetto, $catalogueValueGross];
    }

    /**
     * @param float $price
     * @param int $quantity
     * @param bool $round
     * @param int $precision
     * @return float
     */
    public function calculateGrossValue(float $price, int $quantity, bool $round = true, int $precision = 2): float
    {
        $value = round($price, $precision) * $quantity;
        return $round ? round($value, $precision) : $value;
    }

    /**
     * @param Money $grossPrice
     * @param Value $quantity
     * @return Money
     */
    public function calculateMoneyGrossValue(
        Money $grossPrice,
        Value $quantity
    ): Money {

        return $grossPrice->multiply($quantity->getRealStringAmount());
    }

    /**
     * @param float $price
     * @param int $quantity
     * @param int|null $taxPercentage
     * @param bool $round
     * @param int $precision
     * @return float
     */
    public function calculateNetValueFromGrossPrice(
        float $price,
        int   $quantity,
        ?int  $taxPercentage,
        bool  $round = true,
        int   $precision = 2
    ): float {
        $taxPercentage = (int)$taxPercentage;
        $value = round($price, $precision) * $quantity;
        return $round ? round($value / ((100 + $taxPercentage) / 100), $precision) : $value;
    }

    /**
     * @param Money $grossPrice
     * @param Value $quantity
     * @param Value $taxPercentage
     * @return Money
     * @throws \Exception
     */
    public function calculateMoneyNetValueFromGrossPrice(
        Money $grossPrice,
        Value $quantity,
        Value $taxPercentage
    ): Money {
        $precision = ValueHelper::getCurrencyPrecision($grossPrice->getCurrency()->getCode());
        $grossValue = $grossPrice->multiply($quantity->getAmount());
        return $grossValue->divide((string) ((ValueHelper::get100Percents($precision) + (int)$taxPercentage->getAmount()) / ValueHelper::get100Percents($precision)));
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
        Price    $activePrice,
        array    &$totalRes,
        array    &$spreadRes,
        ?Money   $catalogueValueNetto,
        ?Money   $catalogueValueGross
    ): void {
        $taxRate = $activePrice->getVat(true);

        if ($this->ps->get('cart.calculation.gross')) {
            $valueNetto = $this->calculateMoneyNetValueFromGrossPrice($activePrice->getGrossPrice(true), $selectedCartItem->getQuantity(true), $activePrice->getVat(true));
            $valueGross = $this->calculateMoneyGrossValue($activePrice->getGrossPrice(true), $selectedCartItem->getQuantity(true));

            TaxManager::addMoneyValueToGrossRes($taxRate, $valueGross, $totalRes);

            if ($catalogueValueGross !== null) {
                TaxManager::addValueToGrossRes($taxRate, ($valueGross->lessThan($catalogueValueGross)) ? $catalogueValueGross->subtract($valueGross)  : 0, $spreadRes);
            }
        } else {
            $valueNetto = $this->calculateMoneyNetValue($activePrice->getNetPrice(true), $selectedCartItem->getQuantity(true));
            $valueGross = $this->calculateMoneyGrossValueFromNetPrice($activePrice->getNetPrice(true), $selectedCartItem->getQuantity(true), $activePrice->getVat(true));

            TaxManager::addMoneyValueToNettoRes($taxRate, $valueNetto, $totalRes);
            if ($catalogueValueNetto !== null) {
                TaxManager::addMoneyValueToNettoRes($taxRate, ($catalogueValueNetto->greaterThan($valueNetto)) ? $catalogueValueNetto->subtract($valueNetto) : 0, $spreadRes);
            }
        }
    }

    /**
     * @param float $price
     * @param int $quantity
     * @param bool $round
     * @param int $precision
     * @deprecated
     * @return float
     */
    public function calculateNetValue(float $price, int $quantity, bool $round = true, int $precision = 2): float
    {
        $value = round($price, $precision) * $quantity;
        return $round ? round($value, $precision) : $value;
    }

    /**
     * @param Money $price
     * @param Value $quantity
     * @return Money
     */
    public function calculateMoneyNetValue(
        Money $price,
        Value $quantity
    ): Money {
        return $price->multiply($quantity->getRealStringAmount());
    }

    /**
     * @param float $price
     * @param int $quantity
     * @param int|null $taxPercentage
     * @param bool $round
     * @param int $precision
     * @return float
     * @deprecated
     */
    public function calculateGrossValueFromNetPrice(
        float $price,
        int   $quantity,
        ?int  $taxPercentage,
        bool  $round = true,
        int   $precision = 2
    ): float {
        $taxPercentage = (int)$taxPercentage;
        $value = round($price, $precision) * $quantity;
        return $round ? round($value * (100 + $taxPercentage) / 100, $precision) : $value;
    }

    /**
     * @param Money $netPrice
     * @param Value $quantity
     * @param Value $taxPercentage
     * @return Money
     * @throws \Exception
     */
    public function calculateMoneyGrossValueFromNetPrice(
        Money $netPrice,
        Value $quantity,
        Value $taxPercentage
    ): Money {
        $precision = ValueHelper::getCurrencyPrecision($netPrice->getCurrency()->getCode());
        $grossValue = $netPrice->multiply($quantity->getAmount());
        return $grossValue->multiply(((ValueHelper::get100Percents($precision) + (int) $taxPercentage->getRealStringAmount()) / ValueHelper::get100Percents($precision)));
    }

    /**
     * @param Product $product
     * @param Value $quantity
     * @param Price $activePrice
     * @param array $totalRes
     * @param array $spreadRes
     * @param float|null $catalogueValueNetto
     * @param float|null $catalogueValueGross
     */
    protected function calculateActiveValuesWithProduct(
        Product $product,
        Value   $quantity,
        Price   $activePrice,
        array   &$totalRes,
        array   &$spreadRes,
        ?float  $catalogueValueNetto,
        ?float  $catalogueValueGross
    ): void {
        $taxRate = $activePrice->getVat();

        if ($this->ps->getParameter('cart.calculation.gross')) {
            $valueNetto = $this->calculateNetValueFromGrossPrice($activePrice->getGrossPrice(), (int)$quantity->getAmount(), $activePrice->getVat());
            $valueGross = $this->calculateGrossValue($activePrice->getGrossPrice(), (int)$quantity->getAmount());

            TaxManager::addValueToGrossRes($taxRate, $valueGross, $totalRes);
            if ($catalogueValueGross !== null) {
                TaxManager::addValueToGrossRes($taxRate, ($catalogueValueGross > $valueGross) ? $catalogueValueGross - $valueGross : 0, $spreadRes);
            }
        } else {
            $valueNetto = $this->calculateNetValue($activePrice->getNetPrice(), (int)$quantity->getAmount());
            $valueGross = $this->calculateGrossValueFromNetPrice($activePrice->getNetPrice(), (int)$quantity->getAmount(), $activePrice->getVat());

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
        $price = $this->getPriceForCartItem($selectedCartItem);

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
            $this->rebuildCart($cart, true, false);
        } elseif ($cart->getCartSummary() && $cart->getCartSummary()->getCalculatedAt() !== null) {
            return $cart->getCartSummary();
        }

        $selectedCartItems = $cart->getSelectedCartItems();

        $totalRes = [];
        $spreadRes = [];
        $shippingCostRes = [];

        $totalNet = new Money(0, new MoneyCurrency($cart->getCurrencyIsoCode()));
        $totalGross = new Money(0, new MoneyCurrency($cart->getCurrencyIsoCode()));

        $spreadNet = new Money(0, new MoneyCurrency($cart->getCurrencyIsoCode()));;
        $spreadGross = new Money(0, new MoneyCurrency($cart->getCurrencyIsoCode()));0;

        //Sumy poszczególnych wartości pozycji - nie używać do wyliczania wartości total netto i brutto całego koszyka - tylko do prezentacji
        $totalItemsNet = new Money(0, new MoneyCurrency($cart->getCurrencyIsoCode()));;
        $totalItemsGross = new Money(0, new MoneyCurrency($cart->getCurrencyIsoCode()));;

        $cnt = $cart->getCartItems()->count();
        $cntSelected = $cart->countSelectedItems();

        /**
         * @var CartItem $selectedCartItem
         */
        foreach ($selectedCartItems as $selectedCartItem) {

            //ProductSetProduct
            if ($selectedCartItem->getProduct()->isProductSet()) {


                //TODO verify product set product
                /**
                 * @var ProductSetProduct $productSetProduct
                 */
                foreach ($selectedCartItem->getProduct()->getProductSetProducts() as $productSetProduct) {
                    $product = $productSetProduct->getProduct();
                    $productQuantity = $productSetProduct->getQuantity(true);
                    $productSet = $selectedCartItem->getProduct();
                    $calculatedQuantity = $selectedCartItem->getQuantity(true)->multiply($productQuantity->getRealStringAmount());

                    $cataloguePrice = $this->getCataloguePriceForProduct(
                        $selectedCartItem->getCart(),
                        $product,
                        $productSet,
                        $calculatedQuantity
                    );

                    [$catalogueValueNetto, $catalogueValueGross] = $this->calculateCatalogueValues($selectedCartItem, $cataloguePrice);

                    if ($selectedCartItem->getCartItemSummary()?->isProductSet()
                        && $selectedCartItem->getCartItemSummary()?->getCalculatedAt()
                        && $selectedCartItem->getCartItemSummary()?->hasProductSetProductActivePriceByProductId($product->getId())
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
                //Normal product
                $cataloguePrice = $this->getCataloguePriceForCartItem($selectedCartItem);
                [$catalogueValueNetto, $catalogueValueGross] = $this->calculateCatalogueValues($selectedCartItem, $cataloguePrice);

                if ($selectedCartItem->getCartItemSummary()?->getCalculatedAt() && $selectedCartItem->getCartItemSummary()?->getActivePrice()) {
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
            [$totalProductsNet, $totalProductsGross] = TaxManager::calculateMoneyTotalNettoAndGrossFromGrossRes($cart->getCurrencyIsoCode(), $totalRes, $this->addTax($cart));
        } else {
            [$totalProductsNet, $totalProductsGross] = TaxManager::calculateMoneyTotalNettoAndGrossFromNettoRes($cart->getCurrencyIsoCode(), $totalRes, $this->addTax($cart));
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
            $this->addTax($cart),
            $this->ps->get('cart.calculation.gross') ? $totalProductsGross : $totalProductsNet,
            $shippingCostRes
        );

        [$paymentCostNetto, $paymentCostGross] = $this->calculatePaymentCost($cart, $this->addTax($cart), $totalRes);

        if ($this->ps->get('cart.calculation.gross')) {
            [$shippingCostNetto, $shippingCostGross] = TaxManager::calculateMoneyTotalNettoAndGrossFromGrossRes($cart->getCurrencyIsoCode(), $shippingCostRes, $this->addTax($cart));
        } else {
            [$shippingCostNetto, $shippingCostGross] = TaxManager::calculateMoneyTotalNettoAndGrossFromNettoRes($cart->getCurrencyIsoCode(), $shippingCostRes, $this->addTax($cart));
        }



        //sumaryczna wartość produktów z kosztami dostawy
        $totalWithShippingNettoRes = TaxManager::mergeMoneyRes($shippingCostRes, $totalRes);

        if ($this->ps->get('cart.calculation.gross')) {
            [$totalWithShippingNetto, $totalWithShippingGross] = TaxManager::calculateMoneyTotalNettoAndGrossFromGrossRes(
                $cart->getCurrencyIsoCode(),
                $totalWithShippingNettoRes,
                $this->addTax($cart)
            );
        } else {
            [$totalWithShippingNetto, $totalWithShippingGross] = TaxManager::calculateMoneyTotalNettoAndGrossFromNettoRes(
                $cart->getCurrencyIsoCode(),
                $totalWithShippingNettoRes,
                $this->addTax($cart)
            );
        }

        [$shippingCostFromNetto, $shippingCostFromGross] = $this->getShippingCostFrom($cart, $shippingCostNetto);


        $cartSummary = (new CartSummary)
            ->setCnt($cnt)
            ->setSelectedCnt($cntSelected)
            ->setTotalProductsNet($totalProductsNet)
            ->setTotalProductsGross($totalProductsGross)
            ->setShippingCostNet($shippingCostNetto)
            ->setShippingCostGross($shippingCostGross)
            ->setPaymentCostNet($paymentCostNetto)
            ->setPaymentCostGross($paymentCostGross)
            ->setTotalNet($totalWithShippingNetto)
            ->setTotalGross($totalWithShippingGross)
            ->setSpreadNet($spreadNet)
            ->setSpreadGross($spreadGross)
            ->setCalculatedAt(new \DateTime('NOW'))
            ->setShowVatViesWarning($this->showVatViesWarning($cart))
            ->setFreeShippingThresholdNet(ValueHelper::convertToMoney($freeDeliveryThresholdNetto, $cart->getCurrencyIsoCode()))
            ->setFreeShippingThresholdGross(ValueHelper::convertToMoney($freeDeliveryThresholdGross, $cart->getCurrencyIsoCode()))
            ->setShippingCostFromNet($shippingCostFromNetto)
            ->setShippingCostFromGross($shippingCostFromGross)
            ->setCalculationType($this->ps->get('cart.calculation.gross') ? CartSummary::CALCULATION_TYPE_GROSS : CartSummary::CALCULATION_TYPE_NET)
            ->setCurrencyIsoCode($cart->getCurrencyIsoCode());

        $cart->setCartSummary($cartSummary);

        $cart
            ->setTotalValueGross($cartSummary->getTotalGross(true))
            ->setTotalValueNet($cartSummary->getTotalNet(true));

        $this->eventDispatcher->dispatch(new CartEvent($cart), CartEvents::CART_SUMMARY_CALCULATED);

        return $cartSummary;
    }

    /**
     * Metoda zwraca minimalny koszt dostawy dostępny dla klienta
     * TODO
     *
     * @param Cart $cart
     * @param Money $shippingCostNetto
     * @return array
     */
    protected function getShippingCostFrom(CartInterface $cart, Money $shippingCostNetto): array
    {
        //TODO calculate shippingCostFrom
        return [
            new Money(0, new MoneyCurrency($cart->getCurrencyIsoCode())),
            new Money(0, new MoneyCurrency($cart->getCurrencyIsoCode()))
        ];
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
            && $cart->getBillingContractorCountry()->isUeMember()
            && (
                $cart->getBillingContractorData()->getType() === BillingData::TYPE_COMPANY
                || $cart->getBillingContractorData()->getType() === null
            ) && !$cart->getBillingContractorData()->getIsVatUEActivePayer()
        ) {
            return true;
        }

        return false;
    }

    /**
     * @param Cart $cart
     * @param bool $addVat
     * @param null $calculatedTotalProducts
     * @param array $shippingCostRes
     * @return array
     * @throws \Exception
     */
    public function calculateShippingCost(
        Cart  $cart,
        bool  $addVat,
              $calculatedTotalProducts,
        array &$shippingCostRes
    ): array {
        if (!$cart) {
            $cart = $this->getCart();
        }

        $packages = $cart->getCartPackages();

        $totalNetto = ValueHelper::convertToMoney(0, $cart->getCurrencyIsoCode());
        $totalGross = ValueHelper::convertToMoney(0, $cart->getCurrencyIsoCode());
        $totalGrossRounded = ValueHelper::convertToMoney(0, $cart->getCurrencyIsoCode());
        $taxPercentage = null; //TODO
        $freeDeliveryThresholdNetto = null; //TODO
        $freeDeliveryThresholdGross = null; //TODO

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

        //TODO Zmienić na obiekt wyniku
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
     * @param Cart $cart
     * @param bool $addVat
     * @param array $totalRes
     * @return array
     * @throws \Exception
     */
    protected function calculatePaymentCost(Cart $cart, bool $addVat, array &$totalRes): array
    {
        $totalNetto = ValueHelper::convertToMoney(0, $cart->getCurrencyIsoCode());
        $totalGross = ValueHelper::convertToMoney(0, $cart->getCurrencyIsoCode());

        //TODO do modyfikacji, należy używać produktu specjalnego powiązanego z metodą płatności
        $paymentMethod = $cart->getPaymentMethod();

        $taxRate = ValueHelper::convertToValue(23);

        if ($paymentMethod instanceof PaymentMethod && $paymentMethod->getPrice()) {
            //TODO Fetching payment cost prices from priclists
            $paymentCostNetto = ValueHelper::convertToMoney(0, $cart->getCurrencyIsoCode());
            $paymentCostGross = ValueHelper::convertToMoney(0, $cart->getCurrencyIsoCode());

            $totalNetto->add($paymentCostNetto);
            $totalGross->add($paymentCostGross);

            //Uzupełniamy tablicę netto o koszty dostawy
            TaxManager::addMoneyValueToNettoRes(
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
    public function prepare(CartInterface $cart)
    {
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

        $this->cartManager->flush();
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

        $this->cartManager->persist($storedCart);
        $this->cartManager->flush();

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
     * The basic method for calculating the available stock stocks, calculating the available quantity for an order, keeping the separation between local and remote availability.
     * For use in rebuilding local parcels, backorder, calculating the available total
     *
     * @param Product $product
     * @param int $userQuantity
     * @return array
     * @throws \Exception
     */
    public function calculateQuantityForProduct(Product $product, int $userQuantity)
    {
        $localQuantity = $this->getRawLocalQuantityForProduct($product);

        $localQuantity = $this->storageService->checkReservedQuantity(
            $product->getId(),
            $userQuantity,
            StorageInterface::TYPE_LOCAL,
            $localQuantity
        );

        $requestedRemoteQuantity = ($userQuantity - $localQuantity > 0) ? $userQuantity - $localQuantity : 0;

        //Regardless of the ordercode setting, we do not allow stocks to be booked at this stage
        [$remoteQuantity, $remoteStoragesWithShippingDays, $backOrderPackageQuantity, $remoteStoragesCountBeforeMerge] = $this->storageService->calculateRemoteShippingQuantityAndDays(
            $product,
            $requestedRemoteQuantity,
            false,
            true,
            false
        );

        $localShippingDays = $product->getShippingDays($this->ps->get('localstorage_number'));
        $remoteShippingDaysList = array_keys($remoteStoragesWithShippingDays);
        $remoteShippingDays = end($remoteShippingDaysList);

        $maxShippingDaysForUserQuantity = ($remoteShippingDays > $localShippingDays) ? $remoteShippingDays : $localShippingDays;

        $futureQuantity = 0;
        $localPackageQuantity = $remotePackageQuantity = 0.0;

        if ($userQuantity <= $localQuantity) {
            $localPackageQuantity = $userQuantity;
        } elseif ($userQuantity <= ($localQuantity + $remoteQuantity + $futureQuantity + $backOrderPackageQuantity)) {
            $localPackageQuantity = (float)$localQuantity;
            $remotePackageQuantity = (float)$remoteQuantity;
        } else {
            //The number of items exceeds stock - it is unacceptable at this point
            throw new \Exception('Incorrect number of items in packages');
        }

        //TODO replace with value object
        return [
            (int)$localPackageQuantity, //quantity available for local parcel
            (int)$remotePackageQuantity, //quantity available for remote package
            (int)$backOrderPackageQuantity, //quantity available for a package on request
            (int)$localShippingDays, //local delivery time
            (int)$remoteShippingDays, //remote delivery time
            $remoteStoragesWithShippingDays, //external warehouses with delivery time
            (int)$maxShippingDaysForUserQuantity, //maximum delivery time
            (int)$localQuantity, //local quantity
            (int)$remoteQuantity, //remote quantity
            $remoteStoragesCountBeforeMerge, //stany zdalne przed scaleniem
        ];
    }

    /**
     * A method designed to handle inventory levels based on a working trigger for converting available values.
     *
     * @param Product $product
     * @param int $userQuantity
     * @return array
     * @throws WrongPackageQuantityException
     */
    public function getCalculatedQuantityForProduct(Product $product, int $userQuantity): array
    {
        $localRawQuantity = $this->getRawLocalQuantityForProduct($product);
        $remoteRawQuantity = $this->getRawRemoteQuantityForProduct($product, $userQuantity);

        //Rezerwacja stanu lokalnego
        $localQuantity = $this->storageService->checkReservedQuantity(
            $product->getId(),
            $userQuantity,
            StorageInterface::TYPE_LOCAL,
            $localRawQuantity
        );

        $requestedRemoteQuantity = ($userQuantity - $localQuantity > 0) ? $userQuantity - $localQuantity : 0;

        $remoteQuantity = $this->storageService->checkReservedQuantity(
            $product->getId(),
            $requestedRemoteQuantity,
            StorageInterface::TYPE_EXTERNAL,
            $remoteRawQuantity
        );

        //Uwaga, aktualnie nie ma możliwości ustalenia zdalnego stanu magazynowego dostawcy dlatego pozwalamy na zamówienie każdej ilości w przypadku dostawcy zewnętrznego
        if ($requestedRemoteQuantity > 0
            && $product->isUseSupplier()
            && $product->getSupplier() instanceof Supplier
        ) {
            $remoteQuantity = $requestedRemoteQuantity;
        }

        $backOrderPackageQuantity = ($userQuantity > $localQuantity + $remoteQuantity) && $this->cartService->isBackorderEnabled() ? $userQuantity - $localQuantity - $remoteQuantity : 0;

        $localShippingDays = $product->getShippingDays($this->ps->get('localstorage_number'));
        $remoteShippingDaysList = [StorageInterface::DEFAULT_DELIVERY_TERM];
        $remoteShippingDays = StorageInterface::DEFAULT_DELIVERY_TERM;

        $maxShippingDaysForUserQuantity = ($remoteShippingDays > $localShippingDays) ? $remoteShippingDays : $localShippingDays;

        $futureQuantity = $localPackageQuantity = $remotePackageQuantity = 0;

        if ($userQuantity <= $localQuantity) {
            $localPackageQuantity = $userQuantity;
        } elseif ($userQuantity <= ($localQuantity + $remoteQuantity + $futureQuantity + $backOrderPackageQuantity)) {
            $localPackageQuantity = $localQuantity;
            $remotePackageQuantity = $remoteQuantity;
        } else {
            //liczba sztuk przewyższa zapasy magazynowe - w tym miejscu jest to niedopuszczalne
            throw new WrongPackageQuantityException('Wrong quantity in packages');
        }

        return [
            (int)$localPackageQuantity,
            (int)$remotePackageQuantity,
            (int)$backOrderPackageQuantity,
            (int)$localShippingDays,
            (int)$remoteShippingDays,
            $remoteStoragesWithShippingDays = [],
            (int)$maxShippingDaysForUserQuantity,
            (int)$localQuantity,
            (int)$remoteQuantity,
            $remoteStoragesCountBeforeMerge = []
        ];
    }

    /**
     * The method calculates the states taking into account local states as part of remote accessibility (then the local state can be merged with the remote state)
     *
     * @param Product $product
     * @param float $userQuantity
     * @return array
     * @throws \Exception
     */
    protected function calculateQuantityForProductWithLocalMerge(Product $product, float $userQuantity): array
    {
        [
            $calculatedQuantity,
            $storagesWithShippingDays,
            $backorderPackageQuantity,
            $storagesCountBeforeMerge
        ] = $this->storageService->calculateRemoteShippingQuantityAndDays(
            $product,
            $userQuantity,
            $useLocalStorageAsRemote = true,
            $this->ps->get('cart.ordercode.enabled') ? true : false,
            false
        );

        $shippingDaysList = array_keys($storagesWithShippingDays);
        $shippingDays = end($shippingDaysList);

        //TODO refactor
        return [
            (int)$calculatedQuantity,
            (int)$backorderPackageQuantity,
            (int)$shippingDays,
            $storagesWithShippingDays,
            $storagesCountBeforeMerge,
        ];
    }

    /**
     * @param bool|null $forceReload
     * @param UserInterface|null $user
     * @param ContractorInterface|null $contractor
     * @param bool $requireCreate
     * @param string|null $cartSessionId
     * @param string|null $cartTransactionId
     * @return Cart|null
     * @throws \Exception
     */
    public function getCart(
        ?bool                $forceReload = false,
        ?UserInterface       $user = null,
        ?ContractorInterface $contractor = null,
        bool                 $requireCreate = true,
        ?string              $cartSessionId = null,
        ?string              $cartTransactionId = null
    ): ?Cart {
        if (!$user) {
            $user = $this->getUser();
        }

        //TODO default contractor
        if (!$contractor && $user instanceof User) {
            $contractor = $user->getDefaultBillingContractor();
        }

        $flush = false;

        if ($forceReload) {
            $this->cart = null;
        }

        if ($this->cart === null) {
            $sessionId = $this->getCartSessionId($cartSessionId);
            $transactionId = $this->getCartTransactionId($cartTransactionId);
            $storedCart = null;

            /**
             * Weryfikujemy obecność koszyka sesyjnego
             * @var null|Cart $sessionCart
             */
            $sessionCart = $this->cartManager->getRepository()->getCartForUser(
            //$this->applicationManager->getApplication()->getId(),
                null,
                null,
                $sessionId,
                $this->ps->get('cart.valid.days'),
                CartInterface::CART_TYPE_SESSION
            );

            if ($user instanceof UserInterface) {
                /**
                 * @var null|Cart $storedCart
                 */
                $storedCart = $this->cartManager->getRepository()->getCartForUser(
                    $user->getId(),
                    $contractor ? $contractor->getId() : $contractor,
                    null,
                    $this->ps->get('cart.valid.days'),
                    CartInterface::CART_TYPE_USER
                );
            }

            if ($storedCart && $storedCart->getSessionId()) {
                $storedCart->setSessionId(null);
            }

            if ($transactionId && $sessionCart instanceof Cart && $sessionCart->getTransactionId() !== $transactionId) {
                $sessionCart->setTransactionId($transactionId);
            }

            if ($transactionId && $storedCart instanceof Cart && $storedCart->getTransactionId() !== $transactionId) {
                $storedCart->setTransactionId($transactionId);
            }

            //Scalamy koszyk, jeżeli istnieją dwa
            if ($sessionCart && $storedCart) {
                //w ramach jednego użytkownika istnieją dwa koszyki, sprobujmy je scalić
                $this->cart = $this->mergeCarts($sessionCart, $storedCart);
            } elseif (!$sessionCart && $storedCart) {
                $this->cart = $storedCart;
            } elseif ($sessionCart && !$storedCart && $user) {
                $this->convertSessionCartToUserCart($sessionCart, $user);
                $this->cart = $sessionCart;
            } elseif ($sessionCart) {
                $this->cart = $sessionCart;
            }

            if (!($this->cart instanceof Cart)) {
                //Tworzymy nowy obiekt koszyka
                $this->cart = $this->createNewCart($user, $contractor, $sessionId, $transactionId);
                $flush = true;
            }
        }

        $flush += $this->checkAndUpdateCartCurrency($this->cart);

        if ($requireCreate && $flush || $this->cart->getId() && !$this->cart->getReportCode()) {
            $this->cart->generateReportCode();
            $flush = true;
        }

        if ($requireCreate && !$this->cart->getId() && $flush || $this->cart->getId() && $flush) {
            //Add the reporting code only before the flush
            $this->cartManager->flush();
        }

        return $this->cart;
    }

    /**
     * @param string $oldSessionId
     * @return CartInterface|null
     * @throws \Exception
     * @deprecated
     */
    public function getReassignSessionCart(string $oldSessionId): ?CartInterface
    {
        $sessionCart = $this->cartManager->getRepository()->findOneBy(
            [
                'sessionId' => $oldSessionId,
            ]
        );

        $newSessionId = $this->getCartSessionId();

        if ($sessionCart instanceof Cart) {
            $sessionCart->setSessionId($newSessionId);
        }

        //Note, a flush is being performed
        $this->cartManager->update($sessionCart);

        return $sessionCart;
    }

    public function assignRegisteredUser(UserInterface $user, ?CartInterface $cart = null): Cart
    {
        if (!$cart) {
            $cart = $this->getCart();
        }

        $cart
            ->setUser($user)
            ->setBillingContractor($user->getDefaultBillingContractor());

        //An important change, we do a flush right away
        $this->cartManager->update($cart);

        return $cart;
    }

    /**
     * @param Cart $cart
     * @param Request $request
     */
    public function injectCartItemsThumbs(Cart $cart, Request $request): void
    {
        /** @var CartItem $cartItem */
        foreach ($cart->getCartItems() as $cartItem) {
            if ($cartItem->getProduct() instanceof Product) {
                //TODO
            }
        }
    }

    /**
     * @param Cart $cart
     */
    public function lockCart(Cart $cart): void
    {
        $cart->setIsLocked(true);
    }

    /**
     * @param Cart $cart
     * @param bool $clearCart
     * @throws \Exception
     */
    public function unlockCart(Cart $cart, bool $clearCart = true): void
    {
        if ($clearCart) {
            $this->clearCart($cart);
        }

        $cart->setIsLocked(false);
    }

    /**
     * @param Cart|null $cart
     * @return bool
     * @throws \Exception
     */
    public function isViewable(?Cart $cart = null)
    {
        if (!$cart) {
            $cart = $this->getCart();
        }

        //Upraszczamy, sprawdzamy ilość pozycji niezależnie od typu pozycji
        //Do rozważenia inna organizacja wyciągania typów
        if (!$cart->getItems()->count()) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    public function isBackorderEnabled(): bool
    {
        //zakładamy, że tablica zawiera niezbędne dane
        if ($this->ps->get('order.bundle')['backOrder']['enabled']) {
            return true;
        }

        return false;
    }

    public function unsetUser(?Cart $cart = null): CartInterface
    {
        if (!$cart) {
            $cart = $this->getCart();
        }

        $cart
            ->setUser(null)
            ->setBillingContractor(null);
        //TODO clear string

        //An important change, we do a flush right away
        $this->cartManager->update($cart);

        return $cart;
    }

    /**
     * @param bool|null $value
     */
    public function setSessionRulesAccepted(?bool $value): void
    {
        $this->requestStack->getSession()->set(CartService::CART_SESSION_RULES_ACCEPTED_ATTR_NAME, (bool)$value);
    }

    /**
     * @return bool
     */
    public function getSessionRulesAccepted(): bool
    {
        return $this->requestStack->getSession()->get(CartService::CART_SESSION_RULES_ACCEPTED_ATTR_NAME, false);
    }

    /**
     * Pobiera id sesji koszyka wygenerowane z poziomiu frontu
     *
     * @param string|null $cartTransactionId
     * @return string
     * @throws \Exception
     */
    public function getCartTransactionId(?string $cartTransactionId = null): string
    {
        if (!$cartTransactionId) {
            $request = $this->requestStack->getMasterRequest();
            $cartTransactionId = $request->headers->get(CartService::CART_TRANSACTION_ID_HEADER_KEY);
        }

        if (!$cartTransactionId) {
            //generuje nowe ID sesji koszyka, na podstawie danych lokalnych
            $cartTransactionId = sha1(session_id() . microtime());
        }

        //Sprawdzamy poprawność sesji
        //Długość znaków musi wynosić dokładnie 40 znaków

        if ($cartTransactionId !== null && !$this->checkCartSHAId($cartTransactionId)) {
            throw new \Exception('Wrong cart session id. SHA1 hash is required.');
        }

        $this->setTransactionId($cartTransactionId);

        return $cartTransactionId;
    }

    /**
     * @param string|null $cartSessionId
     * @return bool
     */
    protected function checkCartSHAId(?string $cartSessionId): bool
    {
        return (bool)preg_match('/^[0-9a-f]{40}$/i', $cartSessionId);
    }


    /**
     * @param string $cartSessionId
     */
    public function setSessionId(string $cartSessionId): void
    {
        //$this->session->set(self::CART_SESSION_ID_ATTR_NAME, $cartSessionId);
    }

    /**
     * @param string $cartTransactionId
     */
    public function setTransactionId(string $cartTransactionId): void
    {
        //$this->session->set(self::CART_TRANSACTION_ID_ATTR_NAME, $cartTransactionId);
    }

    /**
     * Czyści ID sesji użytkownika
     */
    public function clearSessionId(): void
    {
        //$this->session->set(self::CART_SESSION_ID_ATTR_NAME, null);
    }

    /**
     * Pobiera id sesji koszyka wygenerowane z poziomiu frontu
     *
     * @param string|null $cartSessionId
     * @return string
     * @throws \Exception
     */
    public function getCartSessionId(?string $cartSessionId = null): string
    {
        //Checks request headers for cart session id
        if (!$cartSessionId) {
            $request = $this->requestStack->getMasterRequest();
            $cartSessionId = $request->headers->get(CartService::CART_SESSION_ID_HEADER_KEY);
        }

        //Generating new cart session id using SHA1
        if (!$cartSessionId) {
            //generuje nowe ID sesji koszyka, na podstawie danych lokalnych
            $cartSessionId = sha1(session_id() . microtime());
        }

        //Sprawdzamy poprawność sesji
        //Długość znaków musi wynosić dokładnie 40 znaków

        if ($cartSessionId !== null && !$this->checkCartSHAId($cartSessionId)) {
            throw new \Exception('Wrong cart session id. SHA1 hash is required.');
        }

        $this->setSessionId($cartSessionId);

        return $cartSessionId;
    }

    /**
     * @param Cart $cart
     * @param bool $clearCustomerData
     * @return int
     * @throws \Exception
     */
    public function clearCart(Cart $cart, bool $clearCustomerData = true): int
    {
        $removedItemsCnt = $cart->getItems()->count();

        $cart
            ->clearCart($clearCustomerData)
            ->unlockCart();

        $this->cartManager->flush();

        $this->eventDispatcher->dispatch(
            new CartEvent($cart),
            CartEvents::CART_CLEARED,
        );

        return $removedItemsCnt;
    }

//    /**
//     * Wyliczanie kosztów dostawy z uwzględnieniem dodatkowej opłaty związanej z miejscem dostawy
//     *
//     * @param Cart $cart
//     * @param bool $addVat
//     * @param array $shippingCostRes
//     * @return array
//     * @deprecated
//     * @deprecated
//     */
//    public function calculateDeliveryCost(Cart $cart, bool $addVat, array &$shippingCostRes): array
//    {
//        $packages = $cart->getPackages();
//
//        $totalNetto = (float)0.00;
//        $totalGrossRounded = (float)0.00;
//        $totalGross = (float)0.00;
//
//        foreach ($packages as $package) {
//            if ($package->getCustomerDelivery() && $package->getCustomerDelivery()->getTransportCharge()) {
//                $tax = $package->getCustomerDelivery()->getTaxRelation() ? $package->getCustomerDelivery()->getTaxRelation()->getValue() : $this->ps->getParameter('default.tax');
//
//                //naliczamy ekstra należność za dostawę dla tego miejsca
//                $transportCost = $package->getCustomerDelivery()->getTransportCost();
//                [$transportCost] = $this->priceListManager->calculatePrice(
//                    $cart->getCurrencyRelation()->getCodeTitle(),
//                    $transportCost,
//                    1,
//                    new \DateTime('now')
//                );
//
//                if ($package->getCustomerDelivery()->getPriceType() === PriceTypeInterface::PRICE_TYPE_NETTO) {
//                    $transportCostNetto = $transportCost;
//                    $transportCostGross = $addVat ? PriceManager::calculateGrossValue(
//                        $transportCost,
//                        $tax
//                    ) : $transportCost;
//                } else {
//                    $transportCostGross = $transportCost;
//                    $transportCostNetto = PriceManager::calculateNettoValue($transportCost, $tax);
//                }
//
//                $totalNetto += $transportCostNetto;
//                $totalGross += $transportCostGross;
//                $totalGrossRounded += $transportCostGross; //@depracted :)
//
//                //Uzupełniamy tablicę netto o koszty dostawy
//                TaxManager::addValueToNettoRes($tax, $transportCost, $shippingCostRes);
//            }
//        }
//
//        return [$totalNetto, $totalGrossRounded, $totalGross];
//    }

    /**
     * Metoda określa czy VAT powinien zostać naliczany dla koszyka
     *
     * @param Cart $cart
     * @return null|bool
     * @throws \Exception
     */
    public function addTax(Cart $cart): ?bool
    {
        return $this->taxManager->addVat(
            null,//$cart->getBillingContractor()->getCountry(), TODO add country support
            $cart->getBillingContractor()?->getType() ?? ContractorInterface::TYPE_PRIVATE,
            $cart->getBillingContractor()?->getTaxNumber(),
            (bool)$cart->getVatCalculationType() //TODO fix
        );
    }

    /**
     * @param array $productIds
     * @param Cart|null $cart
     * @return mixed
     * @throws \Exception
     * @deprecated
     */
    public function getSelectedCartItems(
        array $productIds,
        ?Cart $cart = null
    ): array {
        if (!$cart) {
            $cart = $this->getCart();
        }

        return $this->cartManager->getRepository()->getCartItemsByCartAndProduct(
            $cart->getId(),
            $productIds
        );
    }

    /**
     * @param false $flush
     * @param bool|null $directMarketing
     * @param bool|null $newsletter
     * @return ContractorInterface|null
     * @throws \Exception
     */
    public function updateContractorAgreementsFromCart(
        bool  $flush = false,
        ?bool $directMarketing = null,
        ?bool $newsletter = null
    ): ?ContractorInterface {
        $customer = $this->getUser() ? $this->getUser()->getDefaultCustomer() : null;

        if (!($customer instanceof ContractorInterface)) {
            return null;
        }

//        if ($directMarketing !== null) {
//            $customer->setDirectMarketingAgree($directMarketing);
//        }
//
//        if ($newsletter !== null) {
//            $customer->setNewsletterAgree($newsletter);
//        }

        $this->em->persist($customer);

        if ($flush) {
            $this->em->flush($customer);
        }

        return $customer;
    }

    /**
     * Ręczne zamknięcie koszyka
     *
     * @param Cart $cart
     * @param bool $flush
     */
    public function closeCart(Cart $cart, bool $flush = true): void
    {
        if ($cart->getValidatedStep() < CartInterface::CART_STEP_ORDER_CREATED) {
            $cart->setValidatedStep(CartInterface::CART_STEP_CLOSED_MANUALLY);
            $this->eventDispatcher->dispatch(new CartEvent($cart), CartEvents::CART_CLOSED);

            if ($flush) {
                $this->cartManager->flush();
            }
        }
    }

    /**
     * @param User $user
     * @param bool $flush
     * @throws \Exception
     * @deprecated
     */
    public function closeOpenCartsForHiddenUser(User $user, bool $flush = true): void
    {
        $currentCartId = $this->getCart()->getId();

        if (!$user->isEnabled()) {
            $carts = $this->cartManager->getRepository()->getOpenCartsByUser($user->getId());

            foreach ($carts as $cart) {
                //Zamykamy wszystkie pozostałe otwarte koszyki, za wyjątkiem aktualnie używanego!
                if ($currentCartId == $cart->getId()) {
                    continue;
                }

                $cart->setValidatedStep(CartInterface::CART_STEP_CLOSED_MANUALLY);
                $this->eventDispatcher->dispatch(new CartEvent($cart), CartEvents::CART_CLOSED);
            }
            if ($flush) {
                $this->cartManager->flush();
            }
        }
    }

    /**
     * Czyści flagę scalenia na wszystkich paczkach koszyka
     * Flaga scalenia wyświetlana jest tylko
     *
     * @param Cart $cart
     * @return Cart
     */
    public function clearMergeFlag(Cart $cart): Cart
    {
        foreach ($cart->getCartPackages() as $package) {
            $package->setIsMerged(false);
            $this->cartManager->persist($package);
        }

        return $cart;
    }

    /**
     * Metoda anuluje zapisaną wcześnie walidację w sytuacji przebudowy zawartości paczek
     * Rozważyć dodanie komunikatu flash w takiej sytuacji
     *
     * @param Cart $cart
     */
    public function clearValidatedStep(Cart $cart): void
    {
        $cart->setValidatedStep(null);
    }

    //Metoda musi być przeniesiona poziom wyżej z uwagi na konieczność użycia kilku komponentów
    public function rebuildCart(?Cart $cart = null, bool $flush = true, bool $getCartSummary = true): array
    {
        $cartItemRemoved = false;

        //pobieramy koszyk
        if (!$cart) {
            $cart = $this->getCart(true);
        }

        //Metoda weryfikuje dostępność produktów w koszyku i usuwa produkty, jeżeli przestały być dostępne
        //Usunięcie niedostępnych produktów powinno odbywać się przed wyliczeniem CartSummary, tak aby proces nie odbywał się dwa razy

        //TODO do użycia komponent CartItem
        //$removedUnavailableProducts = $this->removeUnavailableProducts($cart);
        $removedUnavailableProducts = [];

        //Pobieramy wartość koszyka i ustalamy wartości pozycji
        //Do weryfikacji czy tutaj powinno się to odbywać każdorazowo
        if ($getCartSummary) {
            $this->getCartSummary($cart, true);
        }


        $cartItems = $cart->getCartItems();
        //$this->storageManager->clearReservedQuantityArray();

        //odswieżamy notyfikacje i aktualizujemy pozycje w koszyku (np. nastąpiła zmiana stanu mag.)
        $notifications = [];

        /**
         * @var CartItem $cartItem
         */
        foreach ($cartItems as $cartItem) {

            //TODO do użycia komponent CartTime
            //$this->checkQuantityAndPriceForCartItem($cartItem, $notifications);

            if ($cartItem->getId() === null) {
                $cartItemRemoved = true;
            }
        }

        //sprawdzenie domyślnego typu podziału paczek
        //TODO do użycia komponent z paczek
        $this->checkForDefaultCartOverSaleType($cart);
        //TODO do użycia komponent z paczek
        $packagesUpdated = $this->updatePackages($cart);

        if ($packagesUpdated) {
            $this->clearValidatedStep($cart);
        }

        //Jeżeli z koszyka usunięte zostały jakieś produkty, wówczas
        if ($removedUnavailableProducts || $cartItemRemoved || $packagesUpdated) {
            $cart->clearCartSummary();
        }

        if ($flush) {
            $this->em->flush();
        }


        return [$notifications, $cart, $cartItemRemoved || $packagesUpdated];
    }

//    public function useOrderTemplate(Cart $cart, OrderTemplate $orderTemplate, bool $clearCart, array $updateData = []): array
//    {
//        $cart->setOrderTemplate($orderTemplate);
//
//        try {
//            if ($clearCart || $orderTemplate->getType() === OrderTemplate::TYPE_CLOSED) {
//                //Użycie zamkniętego szablonu spowoduje usunięcie wcześniej dodanych pozycji i wybranych miejsc dostaw
//                $this->clearCart($cart);
//            }
//
//            //Wprowadzenie pozycji
//            $result = $this->updateCartItems($cart, $updateData, false, false, true);
//
//            if ($orderTemplate->getType() === OrderTemplate::TYPE_CLOSED) {
//                //Z uwagi na EDI2-193 wyłączamy blokowanie koszyka po dodaniu szablonu zamkniętego, idea szablonu przestaje istnieć
//                //Aktualnie to kolejna pula produktów
//                //$cart->setIsLocked(true);
//            }
//
//            //Aktualizacja miejsc dostaw
//            //Jezeli szablon ma podpięte miejsca dostawy, podpinamy je w koszyku
//            if ($orderTemplate->getOrderTemplateCustomerDeliveries()->count()) {
//                //Uruchamiamy moduł delivery
//                /** @var BaseDeliveryDataModule $deliveryDataModule */
//                $deliveryDataModule = $this->moduleManager->getModuleByName(BaseDeliveryDataModule::NAME);
//                $deliveryDataModule->setDefaultCustomerDeliveries($cart, $orderTemplate->getCustomerDeliveriesWithUuidKeys());
//            }
//
//            $this->em->flush();
//        } catch (\Exception $e) {
//            $result = [];
//        }
//
//        return $result;
//    }

    /**
     * @param UserInterface|null $user
     * @param ContractorInterface|null $contractor
     * @param string $sessionId
     * @param string|null $transactionId
     * @return CartInterface
     * @throws \Exception
     */
    protected function createNewCart(
        ?UserInterface       $user,
        ?ContractorInterface $contractor,
        string               $sessionId,
        ?string              $transactionId = null
    ): CartInterface {

        $cart = $this->cartManager->createNew();

        $cart
            ->setSessionId($sessionId)
            ->setTransactionId($transactionId)
            ->setCurrency($this->getDefaultCurrencyForCart($user, $contractor));

        if ($user instanceof UserInterface) {
            //The shopping cart is associated with the customer and the user
            $cart
                ->setBillingContractor($contractor)
                ->setUser($user)
                ->setType(CartInterface::CART_TYPE_USER);
        } else {
            $cart
                ->setType(CartInterface::CART_TYPE_SESSION);
        }

        $this->cartManager->persist($cart);

        $this->eventDispatcher->dispatch(
            new CartEvent(
                $cart
            ),
            CartEvents::CART_CREATED,

        );

        return $cart;
    }

    /**
     *
     *
     * @param UserInterface|null $user
     * @param ContractorInterface|null $customer
     * @return Currency|null
     * @throws \Exception
     */
    protected function getDefaultCurrencyForCart(
        ?UserInterface       $user = null,
        ?ContractorInterface $customer = null
    ): ?CurrencyInterface {
        if (!$user) {
            $user = $this->getUser();
        }

        if ($user && !$user instanceof UserInterface) {
            throw new \Exception('User class is not supported');
        }


        if (!$customer && $user instanceof UserInterface) {
            $customer = $user->getDefaultBillingContractor();
        }

        //TODO
        //return $this->priceListManager->getCurrency($customer);
        return null;
    }

    /**
     * TODO
     *
     * @param Cart $cart
     * @return bool
     * @throws \Exception
     */
    public function checkAndUpdateCartCurrency(Cart $cart): bool
    {
        return true;
    }
}