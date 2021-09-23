<?php
declare(strict_types=1);

namespace LSB\OrderBundle\CartComponent;

use JetBrains\PhpStorm\Pure;
use LSB\LocaleBundle\Manager\TaxManager;
use LSB\OrderBundle\CartHelper\PriceHelper;
use LSB\OrderBundle\CartHelper\QuantityHelper;
use LSB\OrderBundle\Entity\CartInterface;
use LSB\OrderBundle\Entity\CartItem;
use LSB\OrderBundle\Entity\CartItemInterface;
use LSB\OrderBundle\Entity\CartPackage;
use LSB\OrderBundle\Entity\CartPackageInterface;
use LSB\OrderBundle\Entity\CartPackageItem;
use LSB\OrderBundle\Entity\Package;
use LSB\OrderBundle\Entity\PackageInterface;
use LSB\OrderBundle\Entity\PackageItem;
use LSB\OrderBundle\Entity\PackageItemInterface;
use LSB\OrderBundle\Exception\BackorderQuantityException;
use LSB\OrderBundle\Manager\CartManager;
use LSB\OrderBundle\Manager\CartPackageItemManager;
use LSB\OrderBundle\Manager\CartPackageManager;
use LSB\PricelistBundle\Model\Price;
use LSB\ProductBundle\Entity\ProductInterface;
use LSB\ProductBundle\Entity\Supplier;
use LSB\ProductBundle\Manager\SupplierManager;
use LSB\ProductBundle\Service\StorageService;
use LSB\ShippingBundle\Entity\Method;
use LSB\UtilityBundle\Helper\ValueHelper;
use LSB\UtilityBundle\Interfaces\Base\BasePackageInterface;
use LSB\UtilityBundle\Value\Value;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class PackageSplitCartComponent extends BaseCartComponent
{
    const NAME = 'packageSplit';

    public function __construct(
        TokenStorageInterface            $tokenStorage,
        protected CartManager            $cartManager,
        protected CartPackageManager     $cartPackageManager,
        protected SupplierManager        $supplierManager,
        protected CartPackageItemManager $cartPackageItemManager,
        protected StorageService         $storageService,
        protected ParameterBagInterface  $ps,
        protected PriceHelper            $priceHelper,
        protected QuantityHelper         $quantityHelper
    ) {
        parent::__construct($tokenStorage);
    }

    /**
     * @return CartPackageManager
     */
    public function getCartPackageManager(): CartPackageManager
    {
        return $this->cartPackageManager;
    }

    /**
     * @return SupplierManager
     */
    public function getSupplierManager(): SupplierManager
    {
        return $this->supplierManager;
    }

    /**
     * @return CartPackageItemManager
     */
    public function getCartPackageItemManager(): CartPackageItemManager
    {
        return $this->cartPackageItemManager;
    }

    /**
     * @return StorageService
     */
    public function getStorageService(): StorageService
    {
        return $this->storageService;
    }

    /**
     * Sprawdza możliwość podziału koszyka na paczki, na tej podstawie zmienia ustawienia podziału koszyka (domyślne)
     * TODO - zwraca zawsze NULL, brak flagi pytania
     * @param CartInterface|null $cart
     * @return bool
     * @throws \Exception
     */
    public function checkForDefaultCartOverSaleType(CartInterface $cart = null): bool
    {
        $showOverSaleQuestion = false;
        $waitForBackorderEnabled = $this->ps->get('cart.oversale.wait_for_backorder.enabled');

        $cartItemsCount = 0;

        $availabilityList = [];
        $localAvailability = [];
        $remoteAvailability = [];
        $backorderAvailability = [];

        //sprawdzamy dostępność każdej z pozycji w koszyku

        /**
         * @var CartItem $cartItem
         */
        foreach ($cart->getCartItems() as $cartItem) {
            if (!$cartItem->isSelected()) {
                continue;
            }

            $availabilityList[$cartItem->getAvailability()] = true;

            if ($cartItem->getLocalAvailabilityStatus()) {
                $localAvailability[$cartItem->getLocalAvailabilityStatus()] = true;
            }

            if ($cartItem->getRemoteAvailabilityStatus()) {
                $remoteAvailability[$cartItem->getRemoteAvailabilityStatus()] = true;
            }

            if ($cartItem->getBackorderAvailabilityStatus()) {
                $backorderAvailability[$cartItem->getBackorderAvailabilityStatus()] = true;
            }

            $cartItemsCount++;
        }

        //sprawdzamy rozbieżność statusów dostępności
        $availabilityListCount = count($availabilityList);
        $localAvailabilityCount = count($localAvailability);
        $remoteAvailabilityCount = count($remoteAvailability);
        $backorderAvailabilityCount = count($backorderAvailability);

        if ($cartItemsCount === 0) {
            $cart
                ->setDeliveryVariant(null)
                ->setIsDeliveryVariantSelected(false);
            $showOverSaleQuestion = false;
        } elseif ($availabilityListCount === 0) {
            //ustawiamy pierwszy typ jako domyślny
            $cart
                ->setDeliveryVariant(CartInterface::DELIVERY_VARIANT_ONLY_AVAILABLE)
                ->setIsDeliveryVariantSelected(false);
        } elseif ($availabilityListCount && $localAvailabilityCount && !$remoteAvailabilityCount && !$backorderAvailabilityCount) {
            //Mamy tylko produkty dostępne lokalnie
            $cart->setDeliveryVariant(CartInterface::DELIVERY_VARIANT_ONLY_AVAILABLE);
            $cart->setIsDeliveryVariantSelected(false);
        } elseif (
            $availabilityListCount
            && !$localAvailabilityCount
            && !$backorderAvailabilityCount
            && $remoteAvailabilityCount == 1
            && !array_key_exists(
                CartItemInterface::ITEM_AVAILABLE_FROM_MULTIPLE_REMOTE_STOCKS,
                $remoteAvailability
            )
        ) {
            //Mamy tylko produkty dostępne zdalnie
            //Musimy jednak sprawdzić czy są dostępne z jednego czy kilku magazynów
            $cart
                ->setDeliveryVariant(CartInterface::DELIVERY_VARIANT_WAIT_FOR_ALL)
                ->setIsDeliveryVariantSelected(false);
        } elseif ($availabilityListCount && !$localAvailabilityCount && !$remoteAvailabilityCount && $backorderAvailabilityCount && !$waitForBackorderEnabled) {
            //Towar tylko na zamówienie
            $cart
                ->setDeliveryVariant(CartInterface::DELIVERY_VARIANT_WAIT_FOR_ALL)
                ->setIsDeliveryVariantSelected(false);
        } elseif ($availabilityListCount && !$localAvailabilityCount && !$remoteAvailabilityCount && $backorderAvailabilityCount && $waitForBackorderEnabled) {
            //Towar tylko na zamówienie
            $cart
                ->setDeliveryVariant(CartInterface::DELIVERY_VARIANT_WAIT_FOR_BACKORDER)
                ->setIsDeliveryVariantSelected(false);
        } elseif ($availabilityListCount && $localAvailabilityCount && !$remoteAvailabilityCount && $backorderAvailabilityCount && !$waitForBackorderEnabled) {
            //Mamy tylko stany lokalne i backorder, wymuszamy podział
            $cart
                ->setDeliveryVariant(CartInterface::DELIVERY_VARIANT_ONLY_AVAILABLE)
                ->setIsDeliveryVariantSelected(false);
        } elseif ($availabilityListCount && $localAvailabilityCount && !$remoteAvailabilityCount && $backorderAvailabilityCount && $waitForBackorderEnabled) {
            //Mamy tylko stany lokalne i backorder, wymuszamy podział
            $cart
                ->setDeliveryVariant(CartInterface::DELIVERY_VARIANT_WAIT_FOR_BACKORDER)
                ->setIsDeliveryVariantSelected(false);
        } elseif (
            $availabilityListCount
            && !$localAvailabilityCount
            && $backorderAvailabilityCount
            && $remoteAvailabilityCount === 1
            && !array_key_exists(
                CartItemInterface::ITEM_AVAILABLE_FROM_MULTIPLE_REMOTE_STOCKS,
                $remoteAvailability
            ) && !$waitForBackorderEnabled
        ) {
            $cart
                ->setDeliveryVariant(CartInterface::DELIVERY_VARIANT_WAIT_FOR_ALL)
                ->setIsDeliveryVariantSelected(false);
        } elseif (
            $availabilityListCount
            && !$localAvailabilityCount
            && $backorderAvailabilityCount
            && $remoteAvailabilityCount === 1
            && !array_key_exists(
                CartItemInterface::ITEM_AVAILABLE_FROM_MULTIPLE_REMOTE_STOCKS,
                $remoteAvailability
            ) && $waitForBackorderEnabled
        ) {
            $cart
                ->setDeliveryVariant(CartInterface::DELIVERY_VARIANT_WAIT_FOR_BACKORDER)
                ->setIsDeliveryVariantSelected(false);
        } elseif ($availabilityListCount > 0 && !$cart->getIsDeliveryVariantSelected()) {
            $cart
                ->setDeliveryVariant(CartInterface::DELIVERY_VARIANT_ASK_QUESTION)
                ->setIsDeliveryVariantSelected(false);
            $showOverSaleQuestion = true;
        }


        $this->cartManager->flush();
        return $showOverSaleQuestion;
    }

    /**
     * @param string|null $orderCode
     * @return bool
     */
    public function isOrderCodeSet(?string $orderCode): bool
    {
        if ($orderCode && trim($orderCode) != '') {
            return true;
        }

        return false;
    }

    /**
     * @param CartInterface $cart
     * @param bool $packagesUpdated
     * @return bool
     * @throws \Exception
     */
    public function checkPackagesForZeroQuantityAndDuplicate(CartInterface $cart, bool $packagesUpdated = false): bool
    {
        /**
         * @var CartPackageInterface $cartPackage
         */
        foreach ($cart->getCartPackages() as $cartPackage) {
            $productIds = [];

            /**
             * @var PackageItem $packageItem
             */
            foreach ($cartPackage->getCartPackageItems() as $packageItem) {
                if (!$packageItem->getProduct() instanceof ProductInterface) {
                    continue;
                }

                if ($packageItem->getQuantity(true)->equals(ValueHelper::createValueZero())
                    || (array_key_exists($packageItem->getProduct()->getId(), $productIds)
                        && array_search($this->isOrderCodeSet($packageItem->getOrderCode()) ? $packageItem->getOrderCode() : CartItem::DEFAULT_ORDER_CODE_VALUE, $productIds[$packageItem->getProduct()->getId()]) !== false)
                ) {
                    $cartPackage->removeCartPackageItem($packageItem);
                    $packagesUpdated = true;
                } else {
                    //sprawdzamy duplikaty pozycji
                    $productIds[$packageItem->getProduct()->getId()][] = $this->isOrderCodeSet($packageItem->getOrderCode()) ? $packageItem->getOrderCode() : CartItem::DEFAULT_ORDER_CODE_VALUE;
                }
            }
            //TODO przeliczenie paczki należy wyciągnąć poziom wyżej?
            //$this->getPackageCount($cartPackage);

            //Kasujemy paczkę gdy nie ma żadnej zawartości
            if ($cartPackage->getCartPackageItems()->count() === 0) {
                $packagesUpdated = true;
                $cart->removeCartPackage($cartPackage);
            }
        }

        return $packagesUpdated;
    }

    /**
     * @param CartPackage $cartPackage
     * @param Supplier $supplier
     * @param array $packageItems
     * @param bool $addShippingCost
     * @return CartPackage
     */
    public function createSupplierPackageFromCartPackage(
        CartPackage $cartPackage,
        Supplier    $supplier,
        array       $packageItems,
        bool        $addShippingCost = true
    ): CartPackage {
        $supplierPackage = clone($cartPackage);
        $supplierPackage->getCartPackageItems()->clear();

        /** @var PackageItem $packageItem */
        foreach ($packageItems as $packageItem) {
            $clonedPackageItem = clone($packageItem);
            $supplierPackage->addCartPackageItem($clonedPackageItem);
        }

        $supplierPackage
            ->setSupplier($supplier);
        //->setAddShippingCost($addShippingCost);

        return $supplierPackage;
    }

    /**
     * @param CartItem $cartItem
     * @param Package $package
     * @return int|null
     */
    #[Pure] public function getPackageItemStatusFromCartItem(CartItem $cartItem, Package $package): ?int
    {
        $packageType = $package->getType();

        if ($packageType === PackageInterface::TYPE_FROM_LOCAL_STOCK) {
            return PackageItemInterface::ITEM_AVAILABLE_FROM_LOCAL_STOCK;
        } elseif ($packageType === PackageInterface::TYPE_FROM_REMOTE_STOCK
            && $cartItem->getLocalAvailabilityStatus() > 0
            && $cartItem->getRemoteAvailabilityStatus() === 0
        ) {
            return PackageItemInterface::ITEM_AVAILABLE_FROM_LOCAL_STOCK;
        } elseif ($packageType === PackageInterface::TYPE_FROM_REMOTE_STOCK
            && $cartItem->getRemoteAvailabilityStatus() > 0
        ) {
            return PackageItemInterface::ITEM_AVAILABLE_FROM_REMOTE_STOCK;
        } elseif ($packageType === PackageInterface::TYPE_BACKORDER) {
            return PackageItemInterface::ITEM_AVAILABLE_FOR_BACKORDER;
        }

        return null;
    }

    /**
     * @param CartItem $cartItem
     * @param CartPackage $package
     * @param Value $quantity
     * @param int|null $shippingDays
     * @param int|null $availability
     * @return CartPackage
     */
    public function addNewPackageItemToCartPackage(
        CartItem    $cartItem,
        CartPackage $package,
        Value       $quantity,
        ?int        $shippingDays,
        ?int        $availability
    ): CartPackageInterface {
        $packageItem = ($this->getCartPackageItemManager()->createNew())
            ->setCartPackage($package)
            ->setProduct($cartItem->getProduct())
            ->setCartItem($cartItem)
            ->setQuantity($quantity)
            ->setShippingDays((int)$shippingDays)
            ->setOrderCode($cartItem->getOrderCode())
            ->setAvailabilityStatus($availability)
            ->setProductSet($cartItem->getProductSet())
            ->setProductSetQuantity($cartItem->getProductSetQuantity())
            ->setCurrency($cartItem->getCart()->getCurrency())
            ->setCurrencyIsoCode($cartItem->getCart()?->getCurrency()?->getIsoCode() ?? $cartItem->getCart()->getCurrencyIsoCode());

        $packageItem->getProductData()
            ->setName($cartItem->getProduct()->getName())
            ->setType($cartItem->getProduct()->getType())
            ->setNumber($cartItem->getProduct()->getNumber());

        $packageItem->getProductSetData()
            ->setName($cartItem->getProductSet()?->getName())
            ->setType($cartItem->getProductSet()?->getType())
            ->setNumber($cartItem->getProductSet()?->getNumber());

        $package->addCartPackageItem($packageItem);

        return $package;
    }

    /**
     * @param CartInterface $cart
     * @param CartPackage|null $backorderPackage
     * @param Method|null $defaultCustomerShippingForm
     * @param bool $isBackorderEnabled
     * @return bool
     * @throws \Exception
     */
    public function rebuildPackagesWaitForAllAsBackorder(
        CartInterface        $cart,
        CartPackageInterface $backorderPackage = null,
        ?Method              $defaultCustomerShippingForm = null,
        bool                 $isBackorderEnabled = true
    ) {
        $packagesUpdated = false;

        /** @var CartItem $cartItem */
        foreach ($cart->getCartItems() as $cartItem) {
            if (!$cartItem->isSelected()) {
                continue;
            }

            //W przypadku EDI zakładamy, że wszystkie produkty są dostępne na zamówienie, nie sprawdzamy stanów mag. przy przebudowie paczek
            $availableLocalPackageQuantity = ValueHelper::createValueZero();
            $availableRemotePackageQuantity = ValueHelper::createValueZero();
            $backorderPackageQuantity = $cartItem->getQuantity(true);
            $totalAvailability = $availableLocalPackageQuantity->add($availableRemotePackageQuantity)->add($backorderPackageQuantity);

            /** @var PackageItem $packageItem */
            foreach ($cartItem->getCartPackageItems() as $cartPackageItem) {
                //Paczka lokalna
                if ($cartPackageItem->getCartPackage()->getType() === PackageInterface::TYPE_FROM_LOCAL_STOCK
                    || $cartPackageItem->getCartPackage()->getType() === PackageInterface::TYPE_FROM_REMOTE_STOCK
                ) {
                    $cartItem->removeCartPackageItem($cartPackageItem);
                    $packagesUpdated = true;
                    //Paczka zdalna
                } elseif ($cartPackageItem->getCartPackage()->getType() === PackageInterface::TYPE_BACKORDER) {
                    //Weryfikacja dostępności
                    if (!$isBackorderEnabled || $totalAvailability->equals(ValueHelper::createValueZero())) {
                        $cartItem->removeCartPackageItem($cartPackageItem);
                        $packagesUpdated = true;
                    } elseif (!$totalAvailability->equals($cartPackageItem->getQuantity(true))) {
                        $cartPackageItem
                            ->setQuantity($totalAvailability)
                            ->setAvailabilityStatus($this->getPackageItemStatusFromCartItem($cartItem, $cartPackageItem->getCartPackage()));
                        $packagesUpdated = true;
                    }
                }
            }

            //Paczka backorder
            if ((int)$totalAvailability->getAmount()) {
                if (!$backorderPackage) {
                    $backorderPackage = $this->addBackorderPackage($cart, $defaultCustomerShippingForm);
                }

                $packageItem = $backorderPackage->checkForExistingProduct($cartItem->getProduct(), $cartItem->getOrderCode());

                if (!$packageItem) {
                    $this->addNewPackageItemToCartPackage(
                        $cartItem,
                        $backorderPackage,
                        $totalAvailability,
                        PackageItemInterface::BACKORDER_PACKAGE_ITEM_SHIPPING_DAYS,
                        $this->getPackageItemStatusFromCartItem($cartItem, $backorderPackage)
                    );
                    $packagesUpdated = true;
                }
            }
        }

        //TODO CartHelper?
        $this->dataCartComponent->clearMergeFlag($cart);

        return $packagesUpdated;
    }

    /**
     * @param CartInterface $cart
     * @param Method|null $shippingForm
     * @return CartPackageInterface
     */
    protected function addBackorderPackage(
        CartInterface $cart,
        Method        $shippingForm = null
    ): CartPackageInterface {
        $backorderPackage = $this->getCartPackageManager()->createNew();
        $backorderPackage
            ->setCart($cart)
            ->setType(PackageInterface::TYPE_BACKORDER)
            ->setShippingMethod($shippingForm);

        $cart->addCartPackage($backorderPackage);

        return $backorderPackage;
    }

    /**
     *  Wylicza i zapisuje aktualną wartość koszyka oraz czas dostawy dla paczki, na podstawie najdłuższego czasu oczekiwania na towar z paczki
     *
     * @param CartPackage $package
     * @return CartPackage
     * @throws \Exception
     * @deprecated
     */
    public function getPackageCount(CartPackage $package): CartPackage
    {
        if (!$this->ps->get('cart.package.count')) {
            return $package;
        }

        $cart = $package->getCart();
        $user = $this->getUser();

        $totalProductsNetto = ValueHelper::convertToValue(0);
        $totalGross = ValueHelper::convertToValue(0);

        $totalRes = [];
        $shippingDays = 0;

        /**
         * @var CartPackageItem $packageItem
         */
        foreach ($package->getCartPackageItems() as $packageItem) {
            if (!$packageItem->getCartItem() || !$packageItem->getCartItem()->isSelected()) {
                $package->removeCartPackageItem($packageItem);
                continue;
            }

            if ($packageItem->getCartItem()->getCartItemSummary()
                && $packageItem->getCartItem()->getCartItemSummary()->getCalculatedAt()
                && $packageItem->getCartItem()->getCartItemSummary()->getActivePrice() instanceof Price
            ) {
                $activePrice = $packageItem->getCartItem()->getCartItemSummary()->getActivePrice();
            } else {
                $activePrice = $this->priceHelper->getPriceForCartItem($packageItem->getCartItem());
            }

            //increase total
            if ($this->ps->get('cart.calculation.gross')) {
                $valueNetto = $this->priceHelper->calculateMoneyNetValueFromGrossPrice($activePrice->getGrossPrice(true), $packageItem->getCartItem()->getQuantity(true), $activePrice->getVat(true));
                $valueGross = $this->priceHelper->calculateMoneyGrossValue($activePrice->getGrossPrice(true), $packageItem->getCartItem()->getQuantity(true));
                TaxManager::addMoneyValueToGrossRes($activePrice->getVat(true), $valueGross, $totalRes);
            } else {
                $valueNetto = $this->priceHelper->calculateMoneyNetValue($activePrice->getNetPrice(true), $packageItem->getCartItem()->getQuantity(true));
                $valueGross = $this->priceHelper->calculateMoneyGrossValueFromNetPrice($activePrice->getNetPrice(true), $packageItem->getCartItem()->getQuantity(true), $activePrice->getVat(true));
                TaxManager::addMoneyValueToNettoRes($activePrice->getVat(), $valueNetto, $totalRes);
            }

            $shippingDays = $packageItem->getShippingDays() > $shippingDays ? $packageItem->getShippingDays() : $shippingDays;
        }

        //zaokrąglamy na samym końcu
        if ($this->ps->get('cart.calculation.gross')) {
            [$totalProductsNetto, $totalProductsGross] = TaxManager::calculateMoneyTotalNettoAndGrossFromGrossRes($cart->getCurrencyIsoCode(), $totalRes, $this->dataCartComponent->addTax($cart));
        } else {
            [$totalProductsNetto, $totalProductsGross] = TaxManager::calculateMoneyTotalNettoAndGrossFromNettoRes($cart->getCurrencyIsoCode(), $this->dataCartComponent->addTax($cart));
        }

        $package
            ->setTotalValueNet($totalProductsNetto)
            ->setTotalValueGross($totalProductsGross)
            ->setShippingDays($shippingDays);

        return $package;
    }

    /**
     * @param array $remoteQuantityWithStorages
     * @param array $daysHelper
     */
    public function processSingleRemoteStorages(array $remoteQuantityWithStorages, array &$daysHelper)
    {
        foreach ($remoteQuantityWithStorages as $storageData) {
            $closest = $this->getStorageService()->getClosest($storageData['shippingDays'], $daysHelper, true);

            if ($closest && $closest < $storageData['shippingDays']) {
                $daysHelper[$closest] = $storageData['shippingDays'];
                $daysToUpdate = array_keys($daysHelper, $closest);

                foreach ($daysToUpdate as $dayToUpdate) {
                    if (abs($daysToUpdate - $storageData['shippingDays']) <= PackageInterface::PACKAGE_MAX_PERIOD) {
                        $daysHelper[$dayToUpdate] = $storageData['shippingDays'];
                    }
                }
            } elseif ($closest && $closest > $storageData['shippingDays']) {
                $daysHelper[$storageData['shippingDays']] = $closest;
            } elseif ($closest && $closest == $storageData['shippingDays']) {
                //do nothing?
            } else {
                $daysHelper[$storageData['shippingDays']] = $storageData['shippingDays'];
            }
        }
    }

    /**
     * Metoda pomocnicza do uwzględnienia scalonych dni dostaw dla poszczególnych produktów
     * Następuje zamiana ilości dni na dane wygenerowane przez metodę pomocniczą (2 poziom scalania)
     * Metoda przepisuje istniejące dni dostaw na nowe wartości, na podstawy tablicy daysHelper, która zawiera scalone dni dostaw
     * W przypadku mag. zdalnych, to właśnie dzień dostawy stanowi klucz
     *
     * @param CartItem $cartItem
     * @param array $quantityData
     * @param array $daysHelper
     * @return array
     */
    protected function getStoragesWithDaysHelper(CartItem $cartItem, array &$quantityData, array &$daysHelper): array
    {
        $productId = $cartItem->getProduct()->getId();
        $orderCode = $this->isOrderCodeSet($cartItem->getOrderCode()) ? $cartItem->getOrderCode() : CartItem::DEFAULT_ORDER_CODE_VALUE;

        $availableLocalPackageQuantity = ValueHelper::convertToValue(0);
        $availableRemotePackageQuantity = ValueHelper::convertToValue(0);
        $backorderPackageQuantity = ValueHelper::convertToValue(0);
        $maxShippingDaysForUserQuantity = ValueHelper::convertToValue(0);
        $remoteQuantityWithStorages = [];

        //Ważne, z racji tego, że stan lokalny może zostać scalony do zdalnego, traktujemy wszystkie stany jako zdalne
        if (array_key_exists($productId, $quantityData) && array_key_exists($orderCode, $quantityData[$productId])
        ) {
            $data = $quantityData[$productId][$orderCode];
            $availableLocalPackageQuantity = ValueHelper::convertToValue(0);;
            $availableRemotePackageQuantity = ValueHelper::convertToValue($data['availableRemotePackageQuantity']);
            $backorderPackageQuantity = ValueHelper::convertToValue($data['backorderPackageQuantity']);
            $maxShippingDaysForUserQuantity = ValueHelper::convertToValue($data['maxShippingDaysForUserQuantity']);
            $remoteQuantityWithStorages = $data['remoteQuantityWithStorages'];
            if (array_key_exists('availableLocalPackageQuantity', $data)) {
                $availableLocalPackageQuantity = ValueHelper::convertToValue($data['availableLocalPackageQuantity']);
            }

            if (is_array($remoteQuantityWithStorages)) {
                foreach ($remoteQuantityWithStorages as $days => $storageDelivery) {
                    if (array_key_exists($days, $daysHelper)) {
                        unset($remoteQuantityWithStorages[$days]);
                        $remoteQuantityWithStorages[$daysHelper[$days]] = ValueHelper::convertToValue($storageDelivery);
                    }
                }
            } else {
                $remoteQuantityWithStorages = [];
            }
        }

        return [
            $availableRemotePackageQuantity,
            $backorderPackageQuantity,
            $maxShippingDaysForUserQuantity,
            $remoteQuantityWithStorages,
            $availableLocalPackageQuantity,
        ];
    }

    /**
     * @param CartInterface $cart
     * @param CartPackage|null $localPackage
     * @param CartPackage|null $remotePackage
     * @param CartPackage|null $backorderPackage
     * @param Method|null $defaultCustomerShippingForm
     * @param bool $isBackorderEnabled
     * @return bool
     * @throws \Exception
     */
    protected function rebuildPackagesSendAvailableWithLocalMerge(
        CartInterface $cart,
        CartPackage   $localPackage = null,
        CartPackage   $remotePackage = null,
        CartPackage   $backorderPackage = null,
        ?Method       $defaultCustomerShippingForm = null,
        bool          $isBackorderEnabled = false
    ): bool {
        $packagesUpdated = false;
        //Wymuszamy skasowanie paczki lokalnej, ponieważ istnieje szansa, na scalenie z paczką zdalną
        if ($localPackage) {
            $cart->removeCartPackage($localPackage);
        }

        $quantityData = [];
        $packageMergeStatus = [];

        /**
         * @var CartItemInterface $cartItem
         */
        foreach ($cart->getCartItems() as $cartItem) {
            if (!$cartItem->isSelected()) {
                continue;
            }

            //w tym miejscu zaciągamy już scalone stany (dla poszczególnych produktów z dniami dostaw wyliczone z użyciem alogrytmów)
            [
                $availableRemotePackageQuantity,
                $backorderPackageQuantity,
                $maxShippingDaysForUserQuantity,
                $remoteQuantityWithStorages
            ] =
                $this->quantityHelper->calculateQuantityForProductWithLocalMerge(
                    $cartItem->getProduct(),
                    $cartItem->getQuantity(true)
                );

            $cartItemQuantityData = [
                'availableRemotePackageQuantity' => $availableRemotePackageQuantity,
                'backorderPackageQuantity' => $backorderPackageQuantity,
                'maxShippingDaysForUserQuantity' => $maxShippingDaysForUserQuantity,
                'remoteQuantityWithStorages' => $remoteQuantityWithStorages,
                'cartItemId' => [$cartItem->getId()],
            ];

            //Przepisujemy stany do wspólnej tablicy
            $quantityData[$cartItem->getProduct()->getId()][$this->isOrderCodeSet($cartItem->getOrderCode()) ? $cartItem->getOrderCode() : CartItem::DEFAULT_ORDER_CODE_VALUE] = $cartItemQuantityData;
        }

        $daysHelper = [];

        //Budujemy wspólną listę dostępności na bazie scalonych już stanów
        //W tym przypadku zmieniamy jedynie shipping day, nie ma konieczności scalania stanów
        //Stany są scalane w storageManager, tutaj zmieniamy jedynie dzień dostawy, aby polączyć wysyłki różnych produktów we wspólne paczki
        $this->buildShippingDaysMergedList($quantityData, $daysHelper);

        /**
         * @var CartItem $cartItem
         */
        foreach ($cart->getCartItems() as $cartItem) {
            if (!$cartItem->isSelected()) {
                continue;
            }

            [
                $availableRemotePackageQuantity,
                $backorderPackageQuantity,
                $maxShippingDaysForUserQuantity,
                $remoteQuantityWithStorages
            ] = $this->getStoragesWithDaysHelper($cartItem, $quantityData, $daysHelper);


            //Nowy model podziału
            foreach ($cartItem->getCartPackageItems() as $cartPackageItem) {
                $package = $cartPackageItem->getCartPackage();

                //Oznaczamy status scalania
                if ($availableRemotePackageQuantity->greaterThan(0) && array_key_exists($package->getMaxShippingDays(), $remoteQuantityWithStorages)) {
                    $packageMergeStatus[$package->getId()] = (array_key_exists($package->getId(), $packageMergeStatus) && $packageMergeStatus[$package->getId()]) ? true : $remoteQuantityWithStorages[$package->getMaxShippingDays()]['isMerged'];
                }

                // w pierwszej kolejności sprawdzamy istniejące już package items i zależne paczki
                if (
                    $cartPackageItem->getCartPackage()->getType() === PackageInterface::TYPE_FROM_REMOTE_STOCK
                    || $cartPackageItem->getCartPackage()->getType() === PackageInterface::TYPE_NEXT_SHIPPING
                ) {
                    if ($availableRemotePackageQuantity->equals(ValueHelper::createValueZero())) {
                        $cartItem->removeCartPackageItem($cartPackageItem);
                        $packagesUpdated = true;
                    } elseif (!array_key_exists($package->getMaxShippingDays(), $remoteQuantityWithStorages)
                        || !$remoteQuantityWithStorages[$package->getMaxShippingDays()]['quantityFromStorage']
                    ) {
                        $cartItem->removeCartPackageItem($cartPackageItem);
                        $packagesUpdated = true;
                        $this->cartManager->flush();
                    } elseif ($availableRemotePackageQuantity->greaterThan(ValueHelper::createValueZero())
                        && !$remoteQuantityWithStorages[$package->getMaxShippingDays()]['quantityFromStorage']->equals($cartPackageItem->getQuantity())) {
                        //tutaj powinniśmy mieć tylko paczki, których maxShippingDays pokrywa się z danymi z cart items
                        $cartPackageItem
                            ->setQuantity($remoteQuantityWithStorages[$package->getMaxShippingDays()]['quantityFromStorage'])
                            ->setShippingDays($remoteQuantityWithStorages[$package->getMaxShippingDays()]['shippingDays'])
                            ->setAvailability($this->getPackageItemStatusFromCartItem($cartItem, $cartPackageItem->getCartPackage()));
                        $packagesUpdated = true;
                        unset($remoteQuantityWithStorages[$package->getMaxShippingDays()]);
                    } else {
                        $cartPackageItem->setShippingDays($remoteQuantityWithStorages[$package->getMaxShippingDays()]['shippingDays']);
                        unset($remoteQuantityWithStorages[$package->getMaxShippingDays()]);
                    }
                } elseif ($cartPackageItem->getCartPackage()->getType() === PackageInterface::TYPE_BACKORDER) {
                    //Weryfikacja dostępności
                    if (!$isBackorderEnabled || $backorderPackageQuantity->equals(ValueHelper::createValueZero())) {
                        $cartItem->removeCartPackageItem($cartPackageItem);
                        $packagesUpdated = true;
                    } elseif (!$backorderPackageQuantity->equals($cartPackageItem->getQuantity(true))) {
                        $cartPackageItem
                            ->setQuantity($backorderPackageQuantity)
                            ->setAvailability($this->getPackageItemStatusFromCartItem($cartItem, $cartPackageItem->getCartPackage()));
                        $packagesUpdated = true;
                    }
                }
            }

            //Paczka backorder, z racji tego, że stan mag. dla backorder jest tylko jeden, tworzymy package item tutaj
            if ($backorderPackageQuantity) {
                if (!$backorderPackage) {
                    $backorderPackage = $this->addBackorderPackage($cart, $defaultCustomerShippingForm);
                }

                $backorderPackageItem = $backorderPackage->checkForExistingProduct($cartItem->getProduct(), $cartItem->getOrderCode());

                if ($backorderPackage && !$backorderPackageItem) {
                    $this->addNewPackageItemToCartPackage(
                        $cartItem,
                        $backorderPackage,
                        $backorderPackageQuantity,
                        PackageItemInterface::BACKORDER_PACKAGE_ITEM_SHIPPING_DAYS,
                        $this->getPackageItemStatusFromCartItem($cartItem, $backorderPackage)
                    );
                    $packagesUpdated = true;
                }
            }

            //dla pozostałych max shipping days tworzymy nowe zdalne paczki z pozycjami
            foreach ($remoteQuantityWithStorages as $maxShippingDays => $remoteQuantityData) {
                if ($remoteQuantityData['quantityFromStorage'] > 0) {
                    $remotePackage = $cart->checkForRemotePackageWithMaxShippingDays($maxShippingDays);

                    if (!$remotePackage) {
                        $remotePackage = $this->addRemotePackage(
                            $cart,
                            $defaultCustomerShippingForm,
                            $maxShippingDays,
                            $remoteQuantityData['isMerged']
                        );
                        $packagesUpdated = true;
                    } else {
                        $packageMergeStatus[$remotePackage->getId()] = (array_key_exists($remotePackage->getId(), $packageMergeStatus) && $packageMergeStatus[$remotePackage->getId()]) ? true : $remoteQuantityWithStorages[$maxShippingDays]['isMerged'];
                    }

                    $packageItem = $remotePackage->checkForExistingProduct($cartItem->getProduct(), $cartItem->getOrderCode());

                    if (!$packageItem) {
                        $this->addNewPackageItemToCartPackage(
                            $cartItem,
                            $remotePackage,
                            $remoteQuantityData['quantityFromStorage'],
                            $remoteQuantityData['shippingDays'],
                            $this->getPackageItemStatusFromCartItem($cartItem, $remotePackage)
                        );
                        $packagesUpdated = true;
                    }
                }
            }
        }

        //oznaczanie statusów scalania
        foreach ($cart->getCartPackages() as $package) {
            if (array_key_exists($package->getId(), $packageMergeStatus)) {
                $package->setIsMerged($packageMergeStatus[$package->getId()]);
            }

            //$this->getCartPackageManager()->persist($package);
        }

        $this->cartManager->flush();

        return $packagesUpdated;
    }

    /**
     * Buduję scaloną listę dni na podstawie wcześniej pobranych i scalonych dostaw na poziomie poszczególnych produktów
     * Tutaj, na bazie danych dostaw poszczególnych produktów, wykonujemy kolejny poziom scalenia przesyłek. Kluczem jest ilośc dni dostawy
     * Do sprawdzenia czy metoda jest nadal używana
     *
     * @param $quantityData
     * @param $daysHelper
     */
    protected function buildShippingDaysMergedList(&$quantityData, &$daysHelper)
    {
        //Pierwsze przeście
        foreach ($quantityData as $productId => $quantityDataRow) {
            //if ($this->ps->getParameter('cart.ordercode.enabled')) {
            foreach ($quantityDataRow as $orderCode => $ordercodeQuantityDataRow) {
                $this->processSingleRemoteStorages(
                    $ordercodeQuantityDataRow['remoteQuantityWithStorages'],
                    $daysHelper
                );
            }
            //} else {
            //     $this->processSingleRemoteStorages($quantityDataRow['remoteQuantityWithStorages'], $daysHelper);
            // }
        }
    }

    /**
     * @param CartInterface $cart
     * @param Method|null $shippingForm
     * @param int|null $maxShippingDays
     * @param bool $isMerged
     * @return CartPackageInterface
     */
    protected function addRemotePackage(
        CartInterface $cart,
        Method        $shippingForm = null,
        ?int          $maxShippingDays = null,
        bool          $isMerged = false
    ): CartPackageInterface {
        $remotePackage = $this->getCartPackageManager()->createNew();
        $remotePackage
            ->setCart($cart)
            ->setPackageType(PackageInterface::TYPE_FROM_REMOTE_STOCK)
            ->setMaxShippingDays($maxShippingDays)
            ->setIsMerged($isMerged)
            ->setShippingForm($shippingForm);

        $cart->addCartPackage($remotePackage);

        return $remotePackage;
    }

    /**
     * Przebudowa wszystkich paczek do jednej paczki zdalnej i paczki backorder
     *
     * @param CartInterface $cart
     * @param CartPackage|null $remotePackage
     * @param CartPackage|null $backorderPackage
     * @param Method|null $defaultCustomerShippingForm
     * @param bool $isBackorderEnabled
     * @return bool
     * @throws BackorderQuantityException
     */
    public function rebuildPackagesWaitForAll(
        CartInterface $cart,
        ?CartPackage  $remotePackage = null,
        ?CartPackage  $backorderPackage = null,
        ?Method       $defaultCustomerShippingForm = null,
        bool          $isBackorderEnabled = true
    ): bool {
        $packagesUpdated = false;

        /**
         * @var CartItem $cartItem
         */
        foreach ($cart->getCartItems() as $cartItem) {
            if (!$cartItem->isSelected()) {
                continue;
            }

            [
                $availableLocalPackageQuantity,
                $availableRemotePackageQuantity,
                $backorderPackageQuantity,
                $localShippingDays,
                $remoteShippingDays,
                $remoteQuantityWithStorages,
                $maxShippingDaysForUserQuantity
            ] =
                $this->quantityHelper->calculateQuantityForProduct(
                    $cartItem->getProduct(),
                    $cartItem->getQuantity(true)
                );

            /**
             * @var CartPackageItem $cartPackageItem
             */
            foreach ($cartItem->getCartPackageItems() as $cartPackageItem) {

                //Paczka lokalna
                if ($cartPackageItem->getCartPackage()->getType() === PackageInterface::TYPE_FROM_LOCAL_STOCK
                    || (!$isBackorderEnabled && ($availableLocalPackageQuantity->add($availableRemotePackageQuantity))->isZero())
                ) {
                    $cartItem->removeCartPackageItem($cartPackageItem);
                    $packagesUpdated = true;
                    //Paczka zdalna
                } elseif ($cartPackageItem->getCartPackage()->getType() === PackageInterface::TYPE_FROM_REMOTE_STOCK
                    || $cartPackageItem->getCartPackage()->getType() == PackageInterface::TYPE_NEXT_SHIPPING
                ) {
                    $hasRemotePackageItem = true;
                    if (($availableLocalPackageQuantity->add($availableRemotePackageQuantity))->equals($cartPackageItem->getQuantity(true))) {
                        $cartPackageItem
                            ->setQuantity($availableLocalPackageQuantity->add($availableRemotePackageQuantity))
                            ->setAvailabilityStatus($this->getPackageItemStatusFromCartItem($cartItem, $cartPackageItem->getCartPackage()));
                        $packagesUpdated = true;
                    }
                    $cartPackageItem->setShippingDays($remoteShippingDays ? $remoteShippingDays : $localShippingDays);
                    //Paczka backorder
                } elseif ($cartPackageItem->getCartPackage()->getType() === PackageInterface::TYPE_BACKORDER) {
                    //Weryfikacja dostępności
                    if (!$isBackorderEnabled || $backorderPackageQuantity->isZero()) {
                        $cartItem->removeCartPackageItem($cartPackageItem);
                        $packagesUpdated = true;
                    } elseif (!$backorderPackageQuantity->equals($cartPackageItem->getQuantity(true))) {
                        $cartPackageItem
                            ->setQuantity($backorderPackageQuantity)
                            ->setAvailabilityStatus($this->getPackageItemStatusFromCartItem($cartItem, $cartPackageItem->getCartPackage()));
                        $packagesUpdated = true;
                    }
                }
            }

            //Paczka zdalna (tylko jedna!)
            if (($availableLocalPackageQuantity->add($availableRemotePackageQuantity))->greaterThan(ValueHelper::convertToValue(0))) {
                if (!$remotePackage) {
                    $remotePackage = $this->addRemotePackage($cart, $defaultCustomerShippingForm);
                }

                $packageItem = $remotePackage->checkForExistingProduct($cartItem->getProduct(), $cartItem->getOrderCode());

                if (!$packageItem instanceof PackageItem) {
                    $this->addNewPackageItemToCartPackage(
                        $cartItem,
                        $remotePackage,
                        $availableLocalPackageQuantity->add($availableRemotePackageQuantity),
                        $maxShippingDaysForUserQuantity,
                        $this->getPackageItemStatusFromCartItem($cartItem, $remotePackage)
                    );
                    $packagesUpdated = true;
                }
            }


            //Paczka backorder
            if ($backorderPackageQuantity > 0) {
                if (!$isBackorderEnabled) {
                    throw new BackorderQuantityException('Backorder is disabled, but backorder quantity is greater than 0.');
                }

                if (!$backorderPackage) {
                    $backorderPackage = $this->addBackorderPackage($cart, $defaultCustomerShippingForm);
                }

                $packageItem = $backorderPackage->checkForExistingProduct($cartItem->getProduct(), $cartItem->getOrderCode());

                if (!$packageItem instanceof PackageItem) {
                    $this->addNewPackageItemToCartPackage(
                        $cartItem,
                        $backorderPackage,
                        $backorderPackageQuantity,
                        PackageItemInterface::BACKORDER_PACKAGE_ITEM_SHIPPING_DAYS,
                        $this->getPackageItemStatusFromCartItem($cartItem, $backorderPackage)
                    );
                    $packagesUpdated = true;
                }
            }
        }

        //TODO
        //$this->dataCartComponent->clearMergeFlag($cart);

        return $packagesUpdated;
    }

    /**
     * Sztywna przebudowa paczek, maks.: 1 lokalna, 1 zdalna, 1 backorder
     *
     * @param CartInterface $cart
     * @param CartPackage|null $localPackage
     * @param CartPackage|null $remotePackage
     * @param CartPackage|null $backorderPackage
     * @param Method|null $defaultCustomerShippingForm
     * @return bool
     * @throws \Exception
     */
    public function rebuildPackagesSendAvailableOneLocalOneRemote(
        CartInterface $cart,
        ?CartPackage  $localPackage = null,
        ?CartPackage  $remotePackage = null,
        ?CartPackage  $backorderPackage = null,
        ?Method       $defaultCustomerShippingForm = null,
        bool          $isBackorderEnabled = true
    ): bool {
        $packagesUpdated = false;

        /**
         * @var CartItem $cartItem
         */
        foreach ($cart->getCartItems() as $cartItem) {
            [
                $availableLocalPackageQuantity,
                $availableRemotePackageQuantity,
                $backorderPackageQuantity,
                $localShippingDays,
                $remoteShippingDays,
                $remoteQuantityWithStorages,
                $maxShippingDaysForUserQuantity
            ] =
                $this->quantityHelper->calculateQuantityForProduct(
                    $cartItem->getProduct(),
                    $cartItem->getQuantity(true)
                );
            //Wyslij to co dostępne, w dwóch paczkach + backorder

            /**
             * @var CartPackageItem
             */
            foreach ($cartItem->getCartPackageItems() as $cartPackageItem) {
                //PACZKA LOKALNA
                if ($cartPackageItem->getCartPackage()->getType() === PackageInterface::TYPE_FROM_LOCAL_STOCK) {
                    if ($availableLocalPackageQuantity->equals(ValueHelper::createValueZero())) {
                        $cartItem->removeCartPackageItem($cartPackageItem);
                        $packagesUpdated = true;
                    } else {
                        if (!$availableLocalPackageQuantity->equals($cartPackageItem->getQuantity(true))) {
                            $cartPackageItem
                                ->setQuantity($availableLocalPackageQuantity)
                                ->setAvailability($this->getPackageItemStatusFromCartItem($cartItem, $cartPackageItem->getCartPackage()));
                            $packagesUpdated = true;
                        }
                        $cartPackageItem->setShippingDays($localShippingDays);
                    }

                    //PACZKA BACKORDR
                } elseif ($cartPackageItem->getCartPackage()->getType() === PackageInterface::TYPE_BACKORDER) {
                    //Weryfikacja dostępności
                    if (!$isBackorderEnabled || $backorderPackageQuantity->equals(ValueHelper::createValueZero())) {
                        $cartItem->removeCartPackageItem($cartPackageItem);
                        $packagesUpdated = true;
                    } elseif (!$backorderPackageQuantity->equals($cartPackageItem->getQuantity(true))) {
                        $cartPackageItem
                            ->setQuantity($backorderPackageQuantity)
                            ->setAvailability($this->getPackageItemStatusFromCartItem($cartItem, $cartPackageItem->getCartPackage()));
                        $packagesUpdated = true;
                    }

                    //PACZKA ZDALNA LUB DOSTAWA PRZYSZŁA
                } elseif ($cartPackageItem->getCartPackage()->getType() === PackageInterface::TYPE_FROM_REMOTE_STOCK
                    || $cartPackageItem->getCartPackage()->getType() === PackageInterface::TYPE_NEXT_SHIPPING
                ) {
                    if ($availableRemotePackageQuantity->equals(ValueHelper::createValueZero())) {
                        $cartItem->removeCartPackageItem($cartPackageItem);
                        $packagesUpdated = true;
                    } else {
                        if (!$availableRemotePackageQuantity->equals($cartPackageItem->getQuantity(true))) {
                            $cartPackageItem
                                ->setQuantity($availableRemotePackageQuantity)
                                ->setAvailability($this->getPackageItemStatusFromCartItem($cartItem, $cartPackageItem->getCartPackage()));
                            $packagesUpdated = true;
                        }
                        $cartPackageItem->setShippingDays($remoteShippingDays);
                    }
                }
            }
            //Paczka lokalna
            if ((int)$availableLocalPackageQuantity->getAmount()) {
                if (!$localPackage) {
                    $localPackage = $this->addLocalPackage($cart, $defaultCustomerShippingForm);
                }

                $localPackageItem = $localPackage->checkForExistingProduct($cartItem->getProduct(), $cartItem->getOrderCode());

                if (!$localPackageItem) {
                    $this->addNewPackageItemToCartPackage(
                        $cartItem,
                        $localPackage,
                        $availableLocalPackageQuantity,
                        $localShippingDays,
                        $this->getPackageItemStatusFromCartItem($cartItem, $localPackage)
                    );
                    $packagesUpdated = true;
                }
            }

            //Paczka zdalna
            if ($availableRemotePackageQuantity) {
                if (!$remotePackage) {
                    $remotePackage = $this->addRemotePackage($cart, $defaultCustomerShippingForm);
                }

                $remotePackageItem = $remotePackage->checkForExistingProduct($cartItem->getProduct(), $cartItem->getOrderCode());

                if (!$remotePackageItem) {
                    $this->addNewPackageItemToCartPackage(
                        $cartItem,
                        $remotePackage,
                        $availableRemotePackageQuantity,
                        $remoteShippingDays,
                        $this->getPackageItemStatusFromCartItem($cartItem, $remotePackage)
                    );
                    $packagesUpdated = true;
                }
            }

            //Paczka backorder
            if ($backorderPackageQuantity) {
                if (!$backorderPackage) {
                    $backorderPackage = $this->addBackorderPackage($cart, $defaultCustomerShippingForm);
                }

                $backorderPackageItem = $backorderPackage->checkForExistingProduct($cartItem->getProduct(), $cartItem->getOrderCode());

                if (!$backorderPackageItem) {
                    $this->addNewPackageItemToCartPackage(
                        $cartItem,
                        $backorderPackage,
                        $backorderPackageQuantity,
                        PackageItemInterface::BACKORDER_PACKAGE_ITEM_SHIPPING_DAYS,
                        $this->getPackageItemStatusFromCartItem($cartItem, $backorderPackage)
                    );
                    $packagesUpdated = true;
                }
            }
        }

        //TODO
        //$this->dataCartComponent->clearMergeFlag($cart);
        $this->cartManager->flush();

        return $packagesUpdated;
    }

    /**
     * @param CartInterface $cart
     * @param CartPackage|null $localPackage
     * @param CartPackage|null $backorderPackage
     * @param Method|null $defaultCustomerShippingForm
     * @param bool $isBackorderEnabled
     * @return bool
     * @throws \Exception
     */
    public function rebuildPackagesOnlyAvailable(
        CartInterface $cart,
        ?CartPackage  $localPackage = null,
        ?CartPackage  $backorderPackage = null,
        ?Method       $defaultCustomerShippingForm = null,
        bool          $isBackorderEnabled = true
    ): bool {
        $packagesUpdated = false;

        /**
         * @var CartItem $cartItem
         */
        foreach ($cart->getCartItems() as $cartItem) {
            if (!$cartItem->isSelected()) {
                continue;
            }

            $hasLocalPackageItem = false;
            $hasBackorderPackage = false;

            [
                $availableLocalPackageQuantity,
                $availableRemotePackageQuantity,
                $backorderPackageQuantity,
                $localShippingDays,
                $remoteShippingDays,
                $remoteQuantityWithStorages,
                $maxShippingDaysForUserQuantity,
                $maxLocalQuantity,
                $maxRemoteQuantity
            ] =
                $this->quantityHelper->calculateQuantityForProduct(
                    $cartItem->getProduct(),
                    $cartItem->getQuantity(true)
                );

            //Tylko paczki z lokalnego magazynu

            if ($cart->getDeliveryVariant() === CartInterface::DELIVERY_VARIANT_ONLY_AVAILABLE) {
                //Pozycje tylko w ilości z lokalnego magazynu
                /**
                 * @var CartPackageItem $cartPackageItem
                 */
                foreach ($cartItem->getCartPackageItems() as $cartPackageItem) {

                    //Obsługa paczki lokalnej
                    if ($cartPackageItem->getCartPackage()->getType() === PackageInterface::TYPE_FROM_LOCAL_STOCK) {

                        //sprawdzamy różnicę w ilości sztuk
                        /**
                         * @var Value $availableLocalPackageQuantity
                         */

                        if (!$availableLocalPackageQuantity->equals($cartPackageItem->getQuantity(true))) {
                            $cartPackageItem
                                ->setQuantity($availableLocalPackageQuantity)
                                ->setAvailabilityStatus($this->getPackageItemStatusFromCartItem($cartItem, $cartPackageItem->getCartPackage()));
                            $packagesUpdated = true;
                        }

                        $cartPackageItem->setShippingDays($localShippingDays);
                    } elseif ($isBackorderEnabled && $cartPackageItem->getCartPackage()->getType() === PackageInterface::TYPE_BACKORDER && $backorderPackageQuantity) {
                        //sprawdzamy różnicę w ilości sztuk
                        /**
                         * @var Value $backorderPackageQuantity
                         */
                        if (!$backorderPackageQuantity->equals($cartPackageItem->getQuantity(true))) {
                            $cartPackageItem
                                ->setQuantity($backorderPackageQuantity)
                                ->setAvailabilityStatus($this->getPackageItemStatusFromCartItem($cartItem, $cartPackageItem->getCartPackage()));

                            $packagesUpdated = true;
                        }
                    } elseif (
                        !$availableLocalPackageQuantity ||
                        $cartPackageItem->getCartPackage()->getType() === PackageInterface::TYPE_FROM_REMOTE_STOCK ||
                        $cartPackageItem->getCartPackage()->getType() === PackageInterface::TYPE_NEXT_SHIPPING ||
                        !$isBackorderEnabled ||
                        !$backorderPackageQuantity
                    ) {
                        //usuwamy zbędną pozycję, jeżeli nie jest już dostępna lub zmieniła się dostępność
                        $cartItem->removeCartPackageItem($cartPackageItem);
                        $packagesUpdated = true;
                    }
                }

                //Nie ma lokalnej paczki, a produkt jest dostępny lokalnie


                if ($availableLocalPackageQuantity) {
                    if (!$localPackage) {
                        $localPackage = $this->addLocalPackage($cart, $defaultCustomerShippingForm);
                    }

                    $localPackageItem = $localPackage->checkForExistingProduct($cartItem->getProduct(), $cartItem->getOrderCode());
                    if (!$localPackageItem) {
                        $this->addNewPackageItemToCartPackage(
                            $cartItem,
                            $localPackage,
                            $availableLocalPackageQuantity,
                            $localShippingDays,
                            $this->getPackageItemStatusFromCartItem($cartItem, $localPackage)
                        );
                        $packagesUpdated = true;
                    }
                }

                //Produkt ma zezwolenie na backorder, a nie posiada dedykowanej paczki
                if ($backorderPackageQuantity) {
                    if (!$backorderPackage) {
                        $backorderPackage = $this->addBackorderPackage($cart, $defaultCustomerShippingForm);
                    }

                    $backorderPackageItem = $backorderPackage->checkForExistingProduct($cartItem->getProduct(), $cartItem->getOrderCode());
                    if (!$backorderPackageItem) {
                        $this->addNewPackageItemToCartPackage(
                            $cartItem,
                            $backorderPackage,
                            $backorderPackageQuantity,
                            PackageItem::BACKORDER_PACKAGE_ITEM_SHIPPING_DAYS,
                            $this->getPackageItemStatusFromCartItem($cartItem, $backorderPackage)
                        );
                        $packagesUpdated = true;
                    }
                }
            }
        }

        //$this->dataCartComponent->clearMergeFlag($cart);
        $this->cartManager->flush();

        return $packagesUpdated;
    }

    /**
     * @param CartInterface $cart
     * @param Method|null $shippingForm
     * @return CartPackageInterface
     */
    protected function addLocalPackage(
        CartInterface $cart,
        Method        $shippingForm = null
    ): CartPackageInterface {
        $localPackage = $this->getCartPackageManager()->createNew();
        $localPackage
            ->setCart($cart)
            ->setType(PackageInterface::TYPE_FROM_LOCAL_STOCK)
            ->setShippingMethod($shippingForm);
        $cart->addCartPackage($localPackage);

        return $localPackage;
    }

    /**
     * Funkcja pomocnicza do testów podzialu, aktualnie nieużywana
     *
     * @param $cartItems
     * @return array
     * @throws \Exception
     */
    protected function calculatePackageCount($cartItems): array
    {
        $shippingDays = [];

        foreach ($cartItems as $cartItem) {
            [
                $localPackageQuantity,
                $remotePackageQuantity,
                $localShippingDays,
                $remoteShippingDays,
                $remoteStoragesWithShippingDays
            ] = $this->quantityHelper->calculateQuantityForProduct(
                $cartItem->getProduct(),
                $cartItem->getQuantity(true)
            );

            if ($localPackageQuantity > 0) {
                $shippingDays[] = $localShippingDays;
            }

            if ($remotePackageQuantity > 0) {
                foreach ($remoteStoragesWithShippingDays as $maxShippingDay => $storageData) {
                    $shippingDays[] = $storageData['shippingDays'];
                }
            }
        }

        //mamy shipping days dla każdej z pozycji
        $shippingDays = array_unique($shippingDays);
        natcasesort($shippingDays);
        $daysUsed = 0;
        $packageCount = 0;
        $hasLocalPackage = false;
        $remotePackageCount = 0;
        $remotePackages = [];

        foreach ($shippingDays as $shippingDay) {
            if ($shippingDay / PackageInterface::LOCAL_PACKAGE_MAX_SHIPPING_DAYS <= 1 && $daysUsed == 0) {
                //mamy lokalną paczkę
                $daysUsed = PackageInterface::LOCAL_PACKAGE_MAX_SHIPPING_DAYS;
                $hasLocalPackage = true;
                $packageCount++;
            } elseif (($daysUsed + PackageInterface::PACKAGE_MAX_PERIOD) / $shippingDay <= 1) {
                $maxShippingDays = $daysUsed + PackageInterface::PACKAGE_MAX_PERIOD;


                if (!array_key_exists($maxShippingDays, $remotePackages)) {
                    //nie ma jeszcze takiej paczki
                    $remotePackageCount++;
                    $remotePackages[$maxShippingDays] = $maxShippingDays;
                }

                $daysUsed += PackageInterface::PACKAGE_MAX_PERIOD;
            }
        }

        return [$hasLocalPackage, $remotePackageCount, $remotePackages];
    }

    /**
     * Podział istniejących paczek z typami na dostawcą.
     * W przypadku różnych dostawców i różnych typów, rozbicie będzie wykonane zarówno z uwzględnieniem typu jak i dostawcy.
     *
     * @param CartInterface $cart
     * @param bool $flush
     * @throws \Exception
     */
    public function splitPackagesForSupplier(CartInterface $cart, bool $flush = true): void
    {
        $defaultSupplier = $this->getSupplierManager()->getDefaultSupplier();

        /** @var CartPackage $cartPackage */
        foreach ($cart->getCartPackages() as $cartPackage) {
            $supplierItems = [];
            $requireSplit = false;
            $hasDefaultSupplier = false;
            $packageWithSippingCost = null;

            /** @var PackageItem $packageItem */
            foreach ($cartPackage->getCartPackageItems() as $packageItem) {
                if (!$packageItem->getProduct()) {
                    continue;
                }

                if ($packageItem->getProduct()->getUseSupplier() && $packageItem->getProduct()->getSupplier() instanceof Supplier) {
                    $supplierId = $packageItem->getProduct()->getSupplier()->getId();

                    if ($packageItem->getProduct()->getSupplier()->getIsSeparatePackageRequired()) {
                        $requireSplit = true;
                    }
                } else {
                    $supplierId = $defaultSupplier->getId();
                }

                $supplierItems[$supplierId][] = $packageItem;
            }

            if (array_key_exists($defaultSupplier->getId(), $supplierItems)) {
                $hasDefaultSupplier = true;
            }

            //Dla danego typu paczki, tworzymy nowe paczki z rozbiciem na dostawcę
            //Jeżeli nie występuje dodatkowy dostawca, uzupełniamy jedynie relację
            if (count($supplierItems) <= 1) {
                $supplierId = array_key_first($supplierItems);
                $supplier = $this->getSupplierManager()->getById($supplierId);

                if (!$supplier instanceof Supplier) {
                    throw new \Exception("Supplier {$supplierId} not exists.");
                }

                $cartPackage->setSupplier($supplier);
            } elseif (count($supplierItems) > 1 && $requireSplit) {

                $addShippingCost = false;

                foreach ($supplierItems as $supplierId => $items) {
                    $supplier = $this->getSupplierManager()->getById($supplierId);

                    if (!$hasDefaultSupplier && $packageWithSippingCost === null
                        || $hasDefaultSupplier && $supplier->getId() === $defaultSupplier->getId()
                    ) {
                        $addShippingCost = true;
                    } else {
                        $addShippingCost = false;
                    }

                    $supplierPackage = $this->createSupplierPackageFromCartPackage($cartPackage, $supplier, $items, $addShippingCost);
                    $cart->addCartPackage($supplierPackage);
                }

                $supplierItems = null;
                $cart->removeCartPackage($cartPackage);
            } else {
                //Jeżeli nie możliwe ustalenie dostawcy lub występuje mix, wystawiamy domyślnego dostawcę
                $cartPackage->setSupplier($defaultSupplier);
            }
        }

        $this->getCartPackageManager()->flush();
    }

    /**
     * Aktualizuje zawartość paczek na podstawie aktualnego stanu koszyka
     *
     * @param CartInterface $cart
     * @param bool $splitSupplier
     * @return bool|null
     * @throws \Exception
     */
    public function updatePackages(CartInterface $cart, bool $splitSupplier = false): ?bool
    {
        $this->checkForDefaultCartOverSaleType($cart);
        $this->getStorageService()->clearReservedQuantityArray();

        $defaultShippingForm = null;
        $packagesUpdated = false;

        $user = $this->getUser();

        if ($cart->getDeliveryVariant() === null) {
            return null;
        }

        //Być może warto to przenieść na poziom metod dzielących przesyłki
        [$localPackage, $remotePackage, $backOrderPackage] = $this->processPackagesBeforePackageUpdate($cart);

        //Uwaga! Tylko te metody poniżej mają sprawdzone backordery
        $packagesUpdated = match ($cart->getDeliveryVariant()) {
            CartInterface::DELIVERY_VARIANT_ONLY_AVAILABLE => $this->rebuildPackagesOnlyAvailable(
                $cart,
                $localPackage,
                $backOrderPackage,
                $defaultShippingForm
            ),
            CartInterface::DELIVERY_VARIANT_SEND_AVAILABLE => $this->rebuildPackagesSendAvailableOneLocalOneRemote(
                $cart,
                $localPackage,
                $remotePackage,
                $backOrderPackage,
                $defaultShippingForm
            ),
            CartInterface::DELIVERY_VARIANT_WAIT_FOR_ALL => $this->rebuildPackagesWaitForAll(
                $cart,
                $remotePackage,
                $backOrderPackage,
                $defaultShippingForm
            ),
            CartInterface::DELIVERY_VARIANT_WAIT_FOR_BACKORDER => $this->rebuildPackagesWaitForAllAsBackorder(
                $cart,
                $backOrderPackage,
                $defaultShippingForm
            ),
        };

        $this->checkForDefaultCartOverSaleType($cart);
        $packagesUpdated = $this->checkPackagesForZeroQuantityAndDuplicate($cart, $packagesUpdated);

        if ($splitSupplier) {
            $this->splitPackagesForSupplier($cart, false);
        }

        $this->cartManager->flush();
        return $packagesUpdated;
    }

    /**
     * @param CartInterface $cart
     * @param bool $isBackorderEnabled
     * @return array
     */
    protected function processPackagesBeforePackageUpdate(
        CartInterface $cart,
        bool $isBackorderEnabled = true
    ): array {
        $packages = $cart->getCartPackages();
        $localPackage = null;
        $remotePackage = null;
        $backOrderPackage = null;
        $existingRemotePackagesCount = 0;

        /**
         * @var CartPackage $package
         */
        foreach ($packages as $package) {

            // obsługa kasowania zbędnych paczek i przepisanie paczek do użytku
            if ($package->getType() === BasePackageInterface::PACKAGE_TYPE_FROM_LOCAL_STOCK
                && $cart->getDeliveryVariant() !== CartInterface::DELIVERY_VARIANT_WAIT_FOR_ALL) {
                $localPackage = $package;
            } elseif ($package->getType() === BasePackageInterface::PACKAGE_TYPE_FROM_LOCAL_STOCK
                && $cart->getDeliveryVariant() === CartInterface::DELIVERY_VARIANT_WAIT_FOR_ALL
            ) {
                $cart->removeCartPackage($package);
            } elseif ($package->getType() === BasePackageInterface::PACKAGE_TYPE_FROM_REMOTE_STOCK
                && $cart->getDeliveryVariant() === CartInterface::DELIVERY_VARIANT_ONLY_AVAILABLE
            ) {
                //kasowanie paczek zdalnych
                $cart->removeCartPackage($package);
            } elseif ($package->getType() === BasePackageInterface::PACKAGE_TYPE_FROM_REMOTE_STOCK
                && (
                    $cart->getDeliveryVariant() === CartInterface::DELIVERY_VARIANT_WAIT_FOR_ALL
                    || $cart->getDeliveryVariant() === CartInterface::DELIVERY_VARIANT_WAIT_FOR_BACKORDER
                )
            ) {
                $existingRemotePackagesCount++;
                //gdy mamy więcej niż jedną paczkę zdalną, kasujemy ją
                if ($existingRemotePackagesCount > 1) {
                    $cart->removeCartPackage($package);
                } else {
                    $remotePackage = $package;
                }
            } elseif ($package->getType() === BasePackageInterface::PACKAGE_TYPE_BACKORDER && $this->dataCartComponent->isBackorderEnabled()) {
                $backOrderPackage = $package;
            } elseif ($package->getType() === BasePackageInterface::PACKAGE_TYPE_BACKORDER && !$this->dataCartComponent->isBackorderEnabled()) {
                $cart->removeCartPackage($package);
            } elseif ($package->getType() === BasePackageInterface::PACKAGE_TYPE_FROM_REMOTE_STOCK && $cart->getDeliveryVariant() === CartInterface::DELIVERY_VARIANT_SEND_AVAILABLE) {
                $remotePackage = $package;
            }
        }

        return [$localPackage, $remotePackage, $backOrderPackage];
    }

    /**
     * @param CartInterface $cart
     */
    protected function verifyCartItemsAndPackageItems(CartInterface $cart)
    {
        /**
         * @var CartItem $cartItem
         */
        foreach ($cart->getCartItems() as $cartItem) {
            foreach ($cartItem->getCartPackageItems() as $packageItem) {
                if (!$cartItem->isSelected()) {
                    $cartItem->removeCartPackageItem($packageItem);
                }

                if ($packageItem->getCartPackage() === null) {
                    $cartItem->removeCartPackageItem($packageItem);
                }
            }
        }

        $this->cartManager->flush();
    }
}