<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Calculator;

use Doctrine\ORM\EntityManagerInterface;
use LSB\LocaleBundle\Manager\TaxManager;
use LSB\OrderBundle\CartComponent\CartItemCartComponent;
use LSB\OrderBundle\CartComponent\DataCartComponent;
use LSB\OrderBundle\CartHelper\PriceHelper;
use LSB\OrderBundle\CartModule\PackageShippingCartModule;
use LSB\OrderBundle\Entity\Cart;
use LSB\OrderBundle\Entity\CartInterface;
use LSB\OrderBundle\Entity\CartItem;
use LSB\OrderBundle\Entity\CartItemInterface;
use LSB\OrderBundle\Entity\CartPackage;
use LSB\OrderBundle\Entity\Order;
use LSB\OrderBundle\Entity\OrderInterface;
use LSB\OrderBundle\Entity\OrderPackage;
use LSB\OrderBundle\Entity\OrderPackageInterface;
use LSB\OrderBundle\Event\CartEvent;
use LSB\OrderBundle\Event\CartEvents;
use LSB\OrderBundle\Model\CartSummary;
use LSB\OrderBundle\Service\CartCalculatorService;
use LSB\OrderBundle\Service\CartModuleService;
use LSB\PaymentBundle\Entity\Method as PaymentMethod;
use LSB\PricelistBundle\Calculator\BaseTotalCalculator;
use LSB\PricelistBundle\Calculator\Result;
use LSB\PricelistBundle\Manager\PricelistManager;
use LSB\PricelistBundle\Model\Price;
use LSB\ProductBundle\Entity\Product;
use LSB\ProductBundle\Entity\ProductSetProduct;
use LSB\UtilityBundle\Helper\ValueHelper;
use LSB\UtilityBundle\Value\Value;
use Money\Currency as MoneyCurrency;
use Money\Money;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Class OrderTotalCalculator
 * @package LSB\OrderBundle\Calculator
 */
class CartTotalCalculator extends BaseTotalCalculator
{
    protected const SUPPORTED_CLASS = Cart::class;
    protected const SUPPORTED_POSITION_CLASS = CartPackage::class;

    public function __construct(
        EntityManagerInterface $em,
        EventDispatcherInterface $eventDispatcher,
        TokenStorageInterface $tokenStorage,
        protected ParameterBagInterface $ps,
        protected CartCalculatorService $cartCalculatorService,
        protected DataCartComponent $dataCartComponent,
        protected PricelistManager $pricelistManager,
        protected CartModuleService $cartModuleService,
        protected CartItemCartComponent $cartItemCartComponent,
        protected PriceHelper $priceHelper
    ) {
        parent::__construct($em, $eventDispatcher, $tokenStorage);
    }

    /**
     * @param Order $subject
     * @param array $options
     * @param string|null $applicationCode
     * @param bool $updateSubject
     * @param bool $updatePositions
     * @param array $calculationRes
     * @return Result
     * @throws \Exception
     */
    public function calculateTotal($subject, array $options = [], ?string $applicationCode = null, bool $updateSubject = true, bool $updatePositions = true, array &$calculationRes = []): Result
    {
        if (!$subject instanceof CartInterface) {
            throw new \Exception('Subject must be Cart');
        }

        $calculationRes = [];
        $calculationProductRes = [];
        $calculationShippingRes = [];
        $calculationPaymentCostRest = [];


        $cartSummary = $this->getCartSummary($subject);

        return new Result(
            true,
            $subject->getCurrency(),
            ValueHelper::createMoneyZero($subject->getCurrencyIsoCode()),
            ValueHelper::createMoneyZero($subject->getCurrencyIsoCode()),
            $subject,
            $calculationRes,
            $calculationProductRes,
            $calculationShippingRes,
            $calculationPaymentCostRest,
            $cartSummary
        );
    }

    public function getCartSummary(CartInterface $cart, bool $rebuildPackages = false): CartSummary
    {
        /**
         * @var Cart $cart
         */

        //Aktualizujemy zawartość koszyka
        if ($rebuildPackages) {
            //TODO
            //$this->rebuildCart($cart, true, false);
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
        $spreadGross = new Money(0, new MoneyCurrency($cart->getCurrencyIsoCode()));

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
                        $productActivePrice = $this->dataCartComponent->getPriceForProduct($selectedCartItem->getCart(), $product, $productSet, $calculatedQuantity);
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
                    $activePrice = $this->priceHelper->getPriceForCartItem($selectedCartItem);
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
            [$totalProductsNet, $totalProductsGross] = TaxManager::calculateMoneyTotalNettoAndGrossFromGrossRes($cart->getCurrencyIsoCode(), $totalRes, $this->dataCartComponent->addTax($cart));
        } else {
            [$totalProductsNet, $totalProductsGross] = TaxManager::calculateMoneyTotalNettoAndGrossFromNettoRes($cart->getCurrencyIsoCode(), $totalRes, $this->dataCartComponent->addTax($cart));
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
            $this->dataCartComponent->addTax($cart),
            $this->ps->get('cart.calculation.gross') ? $totalProductsGross : $totalProductsNet,
            $shippingCostRes
        );

        [$paymentCostNetto, $paymentCostGross] = $this->calculatePaymentCost($cart, $this->dataCartComponent->addTax($cart), $totalRes);

        if ($this->ps->get('cart.calculation.gross')) {
            [$shippingCostNetto, $shippingCostGross] = TaxManager::calculateMoneyTotalNettoAndGrossFromGrossRes($cart->getCurrencyIsoCode(), $shippingCostRes, $this->dataCartComponent->addTax($cart));
        } else {
            [$shippingCostNetto, $shippingCostGross] = TaxManager::calculateMoneyTotalNettoAndGrossFromNettoRes($cart->getCurrencyIsoCode(), $shippingCostRes, $this->dataCartComponent->addTax($cart));
        }


        //sumaryczna wartość produktów z kosztami dostawy
        $totalWithShippingNettoRes = TaxManager::mergeMoneyRes($shippingCostRes, $totalRes);

        if ($this->ps->get('cart.calculation.gross')) {
            [$totalWithShippingNetto, $totalWithShippingGross] = TaxManager::calculateMoneyTotalNettoAndGrossFromGrossRes(
                $cart->getCurrencyIsoCode(),
                $totalWithShippingNettoRes,
                $this->dataCartComponent->addTax($cart)
            );
        } else {
            [$totalWithShippingNetto, $totalWithShippingGross] = TaxManager::calculateMoneyTotalNettoAndGrossFromNettoRes(
                $cart->getCurrencyIsoCode(),
                $totalWithShippingNettoRes,
                $this->dataCartComponent->addTax($cart)
            );
        }

        [$shippingCostFromNetto, $shippingCostFromGross] = $this->dataCartComponent->getShippingCostFrom($cart, $shippingCostNetto);


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
            ->setShowVatViesWarning($this->dataCartComponent->showVatViesWarning($cart))
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

        $this->dataCartComponent->getEventDispatcher()->dispatch(new CartEvent($cart), CartEvents::CART_SUMMARY_CALCULATED);

        return $cartSummary;
    }

    /**
     * @param Order $subject
     * @param array $options
     * @param string|null $applicationCode
     * @param bool $updatePositions
     * @return Result
     */
    public function calculatePositions($subject, array $options, ?string $applicationCode, bool $updatePositions = true): Result
    {
        $res = [];
        return new Result(
            false,
            $subject->getCurrency(),
            ValueHelper::createMoneyZero($subject->getCurrencyIsoCode()),
            ValueHelper::createMoneyZero($subject->getCurrencyIsoCode()),
            $subject,
            $res
        );
    }

    /**
     * @param CartItem $selectedCartItem
     * @param Price|null $cataloguePrice
     * @return array
     * @throws \Exception
     */
    public function calculateCatalogueValues(CartItem $selectedCartItem, ?Price $cataloguePrice): array
    {
        if ($cataloguePrice === null) {
            return [null, null];
        }

        if ($this->ps->get('cart.calculation.gross')) {
            $catalogueValueNetto = $this->priceHelper->calculateMoneyNetValueFromGrossPrice(
                $cataloguePrice->getGrossPrice(true),
                $selectedCartItem->getQuantity(true),
                $cataloguePrice->getVat(true)
            );
            $catalogueValueGross = $this->priceHelper->calculateMoneyGrossValue(
                $cataloguePrice->getGrossPrice(true),
                $selectedCartItem->getQuantity(true)
            );
        } else {
            $catalogueValueNetto = $this->priceHelper->calculateMOneyNetValue(
                $cataloguePrice->getNetPrice(true),
                $selectedCartItem->getQuantity(true)
            );

            $catalogueValueGross = $this->priceHelper->calculateMOneyGrossValueFromNetPrice(
                $cataloguePrice->getNetPrice(true),
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
     * @param Money|null $catalogueValueNetto
     * @param Money|null $catalogueValueGross
     * @throws \Exception
     */
    public function calculateActiveValues(
        CartItem $selectedCartItem,
        Price    $activePrice,
        array    &$totalRes,
        array    &$spreadRes,
        ?Money   $catalogueValueNetto,
        ?Money   $catalogueValueGross
    ): void {
        $taxRate = $activePrice->getVat(true);

        if ($this->ps->get('cart.calculation.gross')) {

            $valueNetto = $this->priceHelper->calculateMoneyNetValueFromGrossPrice($activePrice->getGrossPrice(true), $selectedCartItem->getQuantity(true), $activePrice->getVat(true));
            $valueGross = $this->priceHelper->calculateMoneyGrossValue($activePrice->getGrossPrice(true), $selectedCartItem->getQuantity(true));
            TaxManager::addMoneyValueToGrossRes($taxRate, $valueGross, $totalRes);

            if ($catalogueValueGross !== null) {
                TaxManager::addValueToGrossRes($taxRate, ($valueGross->lessThan($catalogueValueGross)) ? $catalogueValueGross->subtract($valueGross) : 0, $spreadRes);
            }
        } else {
            $valueNetto = $this->priceHelper->calculateMoneyNetValue($activePrice->getNetPrice(true), $selectedCartItem->getQuantity(true));
            $valueGross = $this->priceHelper->calculateMoneyGrossValueFromNetPrice($activePrice->getNetPrice(true), $selectedCartItem->getQuantity(true), $activePrice->getVat(true));

            TaxManager::addMoneyValueToNettoRes($taxRate, $valueNetto, $totalRes);
            if ($catalogueValueNetto !== null) {
                TaxManager::addMoneyValueToNettoRes($taxRate, ($catalogueValueNetto->greaterThan($valueNetto)) ? $catalogueValueNetto->subtract($valueNetto) : 0, $spreadRes);
            }
        }
    }

    /**
     * @param Product $product
     * @param Value $quantity
     * @param Price $activePrice
     * @param array $totalRes
     * @param array $spreadRes
     * @param Money|null $catalogueValueNetto
     * @param Money|null $catalogueValueGross
     * @throws \Exception
     */
    public function calculateActiveValuesWithProduct(
        Product $product,
        Value   $quantity,
        Price   $activePrice,
        array   &$totalRes,
        array   &$spreadRes,
        ?Money  $catalogueValueNetto,
        ?Money  $catalogueValueGross
    ): void {
        $taxRate = $activePrice->getVat();

        if ($this->ps->get('cart.calculation.gross')) {
            $valueNetto = $this->priceHelper->calculateMoneyNetValueFromGrossPrice($activePrice->getGrossPrice(true), $quantity, $activePrice->getVat());
            $valueGross = $this->priceHelper->calculateMoneyGrossValue($activePrice->getGrossPrice(), $quantity);

            TaxManager::addMoneyValueToGrossRes($taxRate, $valueGross, $totalRes);
            if ($catalogueValueGross !== null) {
                TaxManager::addMoneyValueToGrossRes($taxRate, ($catalogueValueGross->greaterThan($valueGross)) ? $catalogueValueGross->subtract($valueGross) : ValueHelper::createMoneyZero($activePrice->getCurrencyIsoCode()), $spreadRes);
            }
        } else {
            $valueNetto = $this->priceHelper->calculateMoneyNetValue($activePrice->getNetPrice(true), $quantity);
            $valueGross = $this->priceHelper->calculateMoneyGrossValueFromNetPrice($activePrice->getNetPrice(true), $quantity, $activePrice->getVat());

            TaxManager::addMoneyValueToNettoRes($taxRate, $valueNetto, $totalRes);
            if ($catalogueValueNetto !== null) {
                TaxManager::addMoneyValueToNettoRes($taxRate, ($catalogueValueNetto->greaterThan($valueNetto)) ? $catalogueValueNetto->subtract($valueNetto) : ValueHelper::createMoneyZero($activePrice->getCurrencyIsoCode()), $spreadRes);
            }
        }
    }

    /**
     * @param Cart $cart
     * @param Product $product
     * @param Product|null $productSet
     * @param Value $quantity
     * @return Price|null
     * @throws \Exception
     */
    public function getCataloguePriceForProduct(
        CartInterface $cart,
        Product       $product,
        ?Product      $productSet,
        Value         $quantity
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
     * @throws \Doctrine\DBAL\DBALException|\Exception
     */
    public function getCataloguePriceForCartItem(CartItemInterface $selectedCartItem): ?Price
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

        $packages = $cart->getCartPackages();

        $totalNetto = ValueHelper::convertToMoney(0, $cart->getCurrencyIsoCode());
        $totalGross = ValueHelper::convertToMoney(0, $cart->getCurrencyIsoCode());
        $totalGrossRounded = ValueHelper::convertToMoney(0, $cart->getCurrencyIsoCode());
        $taxPercentage = null; //TODO
        $freeDeliveryThresholdNetto = null; //TODO
        $freeDeliveryThresholdGross = null; //TODO


        $cartShippingModule = $this->cartModuleService->getCartModule(PackageShippingCartModule::NAME);

        /**
         * @var CartPackage $package
         */
        foreach ($packages as $package) {
            $calculation = $cartShippingModule->calculatePackageShippingCost(
                $package,
                $addVat,
                $calculatedTotalProducts,
                $shippingCostRes
            );

            $totalNetto = $totalNetto->add($calculation->getPriceNet());
            $totalGross = $totalGross->add($calculation->getPriceGross());
            $totalGrossRounded = $totalGrossRounded->add($calculation->getPriceGross());
            $freeDeliveryThresholdNetto = $calculation->getFreeDeliveryThresholdValueNet();
            $freeDeliveryThresholdGross = $calculation->getFreeDeliveryThresholdValueGross();
        }

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
    public function calculatePaymentCost(Cart $cart, bool $addVat, array &$totalRes): array
    {
        $totalNetto = ValueHelper::convertToMoney(0, $cart->getCurrencyIsoCode());
        $totalGross = ValueHelper::convertToMoney(0, $cart->getCurrencyIsoCode());

        //TODO do modyfikacji, należy używać produktu specjalnego powiązanego z metodą płatności
        $paymentMethod = $cart->getPaymentMethod();

        $taxRate = ValueHelper::convertToValue(23);

        if ($paymentMethod instanceof PaymentMethod) {
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
}
