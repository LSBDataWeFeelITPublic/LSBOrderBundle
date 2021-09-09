<?php
declare(strict_types=1);

namespace LSB\OrderBundle\CartModule;

use JetBrains\PhpStorm\Pure;
use LSB\LocaleBundle\Manager\TaxManager;
use LSB\OrderBundle\CartComponent\CartItemCartComponent;
use LSB\OrderBundle\CartComponent\DataCartComponent;
use LSB\OrderBundle\CartComponent\PackageSplitCartComponent;
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
use LSB\PricelistBundle\Model\Price;
use LSB\ProductBundle\Entity\ProductInterface;
use LSB\ProductBundle\Entity\Supplier;
use LSB\ShippingBundle\Entity\Method;
use LSB\UtilityBundle\Helper\ValueHelper;
use LSB\UtilityBundle\Interfaces\Base\BasePackageInterface;
use LSB\UtilityBundle\Value\Value;
use Symfony\Component\HttpFoundation\Request;

class PackageSplitCartModule extends BaseCartModule
{
    const NAME = 'packageSplit';

    public function __construct(
        DataCartComponent                   $dataCartComponent,
        protected PackageSplitCartComponent $packageSplitCartComponent,
        protected CartItemCartComponent     $cartItemCartComponent
    ) {
        parent::__construct($dataCartComponent);
    }

    /**
     * @param CartInterface|null $cart
     * @param Request $request
     * @return \LSB\OrderBundle\Model\CartModuleProcessResult|mixed|void
     */
    public function process(?CartInterface $cart, Request $request)
    {
    }

    /**
     * @param CartInterface $cart
     * @return CartPackage[]|null[]
     */
    protected function processPackagesBeforePackageUpdate(CartInterface $cart): array
    {


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

        $this->dataCartComponent->getCartManager()->flush();
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
        $this->packageSplitCartComponent->checkForDefaultCartOverSaleType($cart);
        $this->packageSplitCartComponent->getStorageService()->clearReservedQuantityArray();

        $defaultShippingForm = null;
        $packagesUpdated = false;

        $user = $this->packageSplitCartComponent->getUser();
        $customer = $user ? $user->getDefaultBillingContractor() : null;

        if ($cart->getDeliveryVariant() === null) {
            return null;
        }

        //Być może warto to przenieść na poziom metod dzielących przesyłki
        [$localPackage, $remotePackage, $backOrderPackage] = $this->processPackagesBeforePackageUpdate($cart);

        //Uwaga! Tylko te metody poniżej mają sprawdzone backordery
        switch ($cart->getDeliveryVariant()) {
            //Tylko produkty dostępne lokalnie + odzielna paczka backorder
            case CartInterface::DELIVERY_VARIANT_ONLY_AVAILABLE:
                $packagesUpdated = $this->rebuildPackagesOnlyAvailable($cart, $localPackage, $backOrderPackage, $defaultShippingForm);
                break;
            //Rozbija dostawy na paczki + paczka backorder
            //Ważne, z racji tego, że stan lokalny może zostać scalony do zdalnego, traktujemy wszystkie stany jako zdalne!
            case CartInterface::DELIVERY_VARIANT_SEND_AVAILABLE:
                //Używamy jednej z trzech metod
                //METODY ZAKOMENTOWANE NIE SĄ AKTYWNE DLA OBSŁUGI ORDERCODE
                //rebuildPackagesSendAvailableWithLocalMerge - scalanie paczek lokalnych i zdalnych
                //rebuildPackagesSendAvailableOneLocalOneRemote - sztywny podział na jedną pczakę lokalną i jedną paczkę zdalną
                $packagesUpdated = $this->rebuildPackagesSendAvailableOneLocalOneRemote($cart, $localPackage, $remotePackage, $backOrderPackage, $defaultShippingForm);

                break;
            //Scala wszystkie pozycji do jednej paczki zdalnej + paczka backorder
            case CartInterface::DELIVERY_VARIANT_WAIT_FOR_ALL:
                $packagesUpdated = $this->rebuildPackagesWaitForAll($cart, $remotePackage, $backOrderPackage, $defaultShippingForm);
                break;
            //Scala wszystkie pozycje do jednej paczki backorder
            case CartInterface::DELIVERY_VARIANT_WAIT_FOR_BACKORDER:
                $packagesUpdated = $this->rebuildPackagesWaitForAllAsBackorder($cart, $backOrderPackage, $defaultShippingForm);
                break;
        }

        $this->packageSplitCartComponent->checkForDefaultCartOverSaleType($cart);
        $packagesUpdated = $this->packageSplitCartComponent->checkPackagesForZeroQuantityAndDuplicate($cart, $packagesUpdated);

        if ($splitSupplier) {
            $this->splitPackagesForSupplier($cart, false);
        }

        $this->dataCartComponent->getCartManager()->flush();

        return $packagesUpdated;
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
        $defaultSupplier = $this->packageSplitCartComponent->getSupplierManager()->getDefaultSupplier();

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
                $supplier = $this->packageSplitCartComponent->getSupplierManager()->getSupplierById($supplierId);

                if (!$supplier instanceof Supplier) {
                    throw new \Exception("Supplier {$supplierId} not exists.");
                }

                $cartPackage->setSupplier($supplier);
            } elseif (count($supplierItems) > 1 && $requireSplit) {

                $addShippingCost = false;

                foreach ($supplierItems as $supplierId => $items) {
                    $supplier = $this->packageSplitCartComponent->getSupplierManager()->getSupplierById($supplierId);

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

        $this->packageSplitCartComponent->getCartPackageManager()->flush();
    }

    /**
     * @param CartPackage $cartPackage
     * @param Supplier $supplier
     * @param array $packageItems
     * @param bool $addShippingCost
     * @return CartPackage
     */
    protected function createSupplierPackageFromCartPackage(
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
    #[Pure] protected function getPackageItemStatusFromCartItem(CartItem $cartItem, Package $package): ?int
    {
        $packageType = $package->getType();

        if ($packageType === PackageInterface::PACKAGE_TYPE_FROM_LOCAL_STOCK) {
            return PackageItemInterface::ITEM_AVAILABLE_FROM_LOCAL_STOCK;
        } elseif ($packageType === PackageInterface::PACKAGE_TYPE_FROM_REMOTE_STOCK
            && $cartItem->getLocalAvailabilityStatus() > 0
            && $cartItem->getRemoteAvailabilityStatus() === 0
        ) {
            return PackageItemInterface::ITEM_AVAILABLE_FROM_LOCAL_STOCK;
        } elseif ($packageType === PackageInterface::PACKAGE_TYPE_FROM_REMOTE_STOCK
            && $cartItem->getRemoteAvailabilityStatus() > 0
        ) {
            return PackageItemInterface::ITEM_AVAILABLE_FROM_REMOTE_STOCK;
        } elseif ($packageType === PackageInterface::PACKAGE_TYPE_BACKORDER) {
            return PackageItemInterface::ITEM_AVAILABLE_FOR_BACKORDER;
        }

        return null;
    }

    /**
     * Metody pomocnicze do przebudowy paczek, tylko dostępne lokalnie
     */
    protected function rebuildPackagesOnlyAvailable(
        CartInterface $cart,
        ?CartPackage  $localPackage = null,
        ?CartPackage  $backorderPackage = null,
        ?Method       $defaultCustomerShippingForm = null
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
                $this->dataCartComponent->calculateQuantityForProduct(
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
                    if ($cartPackageItem->getCartPackage()->getType() === PackageInterface::PACKAGE_TYPE_FROM_LOCAL_STOCK) {

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
                    } elseif ($this->dataCartComponent->isBackorderEnabled() && $cartPackageItem->getCartPackage()->getType() === PackageInterface::PACKAGE_TYPE_BACKORDER && $backorderPackageQuantity) {
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
                        $cartPackageItem->getCartPackage()->getType() === PackageInterface::PACKAGE_TYPE_FROM_REMOTE_STOCK ||
                        $cartPackageItem->getCartPackage()->getType() === PackageInterface::PACKAGE_TYPE_NEXT_SHIPPING ||
                        !$this->dataCartComponent->isBackorderEnabled() ||
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

        $this->dataCartComponent->clearMergeFlag($cart);
        //$this->dataCartComponent->getCartManager()->flush();

        return $packagesUpdated;
    }

    /**
     * Przebudowa wszystkich paczek do jednej paczki zdalnej i paczki backorder
     *
     * @param CartInterface $cart
     * @param CartPackage|null $remotePackage
     * @param CartPackage|null $backorderPackage
     * @param Method|null $defaultCustomerShippingForm
     * @return bool
     * @throws BackorderQuantityException
     */
    protected function rebuildPackagesWaitForAll(
        CartInterface $cart,
        ?CartPackage  $remotePackage = null,
        ?CartPackage  $backorderPackage = null,
        ?Method       $defaultCustomerShippingForm = null
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
                $this->dataCartComponent->calculateQuantityForProduct(
                    $cartItem->getProduct(),
                    $cartItem->getQuantity(true)
                );

            /**
             * @var CartPackageItem $cartPackageItem
             */
            foreach ($cartItem->getCartPackageItems() as $cartPackageItem) {

                //Paczka lokalna
                if ($cartPackageItem->getCartPackage()->getType() === PackageInterface::PACKAGE_TYPE_FROM_LOCAL_STOCK
                    || (!$this->dataCartComponent->isBackorderEnabled() && ($availableLocalPackageQuantity->add($availableRemotePackageQuantity))->isZero())
                ) {
                    $cartItem->removeCartPackageItem($cartPackageItem);
                    $packagesUpdated = true;
                    //Paczka zdalna
                } elseif ($cartPackageItem->getCartPackage()->getType() === PackageInterface::PACKAGE_TYPE_FROM_REMOTE_STOCK
                    || $cartPackageItem->getCartPackage()->getType() == PackageInterface::PACKAGE_TYPE_NEXT_SHIPPING
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
                } elseif ($cartPackageItem->getCartPackage()->getType() === PackageInterface::PACKAGE_TYPE_BACKORDER) {
                    //Weryfikacja dostępności
                    if (!$this->dataCartComponent->isBackorderEnabled() || $backorderPackageQuantity->isZero()) {
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
                if (!$this->dataCartComponent->isBackorderEnabled()) {
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

        $this->dataCartComponent->clearMergeFlag($cart);

        return $packagesUpdated;
    }

    /**
     * @param CartInterface $cart
     * @param CartPackage|null $backorderPackage
     * @param Method|null $defaultCustomerShippingForm
     * @return bool
     */
    protected function rebuildPackagesWaitForAllAsBackorder(
        CartInterface        $cart,
        CartPackageInterface $backorderPackage = null,
        ?Method              $defaultCustomerShippingForm = null
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
                if ($cartPackageItem->getCartPackage()->getType() === PackageInterface::PACKAGE_TYPE_FROM_LOCAL_STOCK
                    || $cartPackageItem->getCartPackage()->getType() === PackageInterface::PACKAGE_TYPE_FROM_REMOTE_STOCK
                ) {
                    $cartItem->removeCartPackageItem($cartPackageItem);
                    $packagesUpdated = true;
                    //Paczka zdalna
                } elseif ($cartPackageItem->getCartPackage()->getType() === PackageInterface::PACKAGE_TYPE_BACKORDER) {
                    //Weryfikacja dostępności
                    if (!$this->dataCartComponent->isBackorderEnabled() || $totalAvailability->equals(ValueHelper::createValueZero())) {
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

        $this->dataCartComponent->clearMergeFlag($cart);

        return $packagesUpdated;
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
            ] = $this->dataCartComponent->calculateQuantityForProduct(
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
        ?Method       $defaultCustomerShippingForm = null
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
                $this->dataCartComponent->calculateQuantityForProduct(
                    $cartItem->getProduct(),
                    $cartItem->getQuantity(true)
                );
            //Wyslij to co dostępne, w dwóch paczkach + backorder

            /**
             * @var CartPackageItem
             */
            foreach ($cartItem->getCartPackageItems() as $cartPackageItem) {
                //PACZKA LOKALNA
                if ($cartPackageItem->getCartPackage()->getType() === PackageInterface::PACKAGE_TYPE_FROM_LOCAL_STOCK) {
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
                } elseif ($cartPackageItem->getCartPackage()->getType() === PackageInterface::PACKAGE_TYPE_BACKORDER) {
                    //Weryfikacja dostępności
                    if (!$this->dataCartComponent->isBackorderEnabled() || $backorderPackageQuantity->equals(ValueHelper::createValueZero())) {
                        $cartItem->removeCartPackageItem($cartPackageItem);
                        $packagesUpdated = true;
                    } elseif (!$backorderPackageQuantity->equals($cartPackageItem->getQuantity(true))) {
                        $cartPackageItem
                            ->setQuantity($backorderPackageQuantity)
                            ->setAvailability($this->getPackageItemStatusFromCartItem($cartItem, $cartPackageItem->getCartPackage()));
                        $packagesUpdated = true;
                    }

                    //PACZKA ZDALNA LUB DOSTAWA PRZYSZŁA
                } elseif ($cartPackageItem->getCartPackage()->getType() === PackageInterface::PACKAGE_TYPE_FROM_REMOTE_STOCK
                    || $cartPackageItem->getCartPackage()->getType() === PackageInterface::PACKAGE_TYPE_NEXT_SHIPPING
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

        $this->dataCartComponent->clearMergeFlag($cart);

        $this->dataCartComponent->getCartManager()->flush();

        return $packagesUpdated;
    }

    /**
     * @param CartInterface $cart
     * @param CartPackage|null $localPackage
     * @param CartPackage|null $remotePackage
     * @param CartPackage|null $backorderPackage
     * @param Method|null $defaultCustomerShippingForm
     * @return bool
     * @throws \Exception
     */
    protected function rebuildPackagesSendAvailableWithLocalMerge(
        CartInterface $cart,
        CartPackage   $localPackage = null,
        CartPackage   $remotePackage = null,
        CartPackage   $backorderPackage = null,
        ?Method       $defaultCustomerShippingForm = null
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
                $this->dataCartComponent->calculateQuantityForProductWithLocalMerge(
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
            $quantityData[$cartItem->getProduct()->getId()][$this->packageSplitCartComponent->isOrderCodeSet($cartItem->getOrderCode()) ? $cartItem->getOrderCode() : CartItem::DEFAULT_ORDER_CODE_VALUE] = $cartItemQuantityData;
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
                    $cartPackageItem->getCartPackage()->getType() === PackageInterface::PACKAGE_TYPE_FROM_REMOTE_STOCK
                    || $cartPackageItem->getCartPackage()->getType() === PackageInterface::PACKAGE_TYPE_NEXT_SHIPPING
                ) {
                    if ($availableRemotePackageQuantity->equals(ValueHelper::createValueZero())) {
                        $cartItem->removeCartPackageItem($cartPackageItem);
                        $packagesUpdated = true;
                    } elseif (!array_key_exists($package->getMaxShippingDays(), $remoteQuantityWithStorages)
                        || !$remoteQuantityWithStorages[$package->getMaxShippingDays()]['quantityFromStorage']
                    ) {
                        $cartItem->removeCartPackageItem($cartPackageItem);
                        $packagesUpdated = true;
                        $this->dataCartComponent->getCartManager()->flush();
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
                } elseif ($cartPackageItem->getCartPackage()->getType() === PackageInterface::PACKAGE_TYPE_BACKORDER) {
                    //Weryfikacja dostępności
                    if (!$this->dataCartComponent->isBackorderEnabled() || $backorderPackageQuantity->equals(ValueHelper::createValueZero())) {
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

            //$this->packageSplitCartComponent->getCartPackageManager()->persist($package);
        }

        $this->dataCartComponent->getCartManager()->flush();

        return $packagesUpdated;
    }

    /**
     * Dodaje nową pozycję w paczce
     *
     * @param CartItem $cartItem
     * @param CartPackage $package
     * @param Value $quantity
     * @param int|null $shippingDays
     * @param int|null $availability
     * @return CartPackage
     */
    protected function addNewPackageItemToCartPackage(
        CartItem    $cartItem,
        CartPackage $package,
        Value       $quantity,
        ?int        $shippingDays,
        ?int        $availability
    ): CartPackageInterface {
        $packageItem = ($this->packageSplitCartComponent->getCartPackageItemManager()->createNew())
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
     * @param Method|null $shippingForm
     * @return CartPackageInterface
     */
    protected function addLocalPackage(
        CartInterface $cart,
        Method        $shippingForm = null
    ): CartPackageInterface {
        $localPackage = $this->packageSplitCartComponent->getCartPackageManager()->createNew();
        $localPackage
            ->setCart($cart)
            ->setType(PackageInterface::PACKAGE_TYPE_FROM_LOCAL_STOCK)
            ->setShippingMethod($shippingForm);
        $cart->addCartPackage($localPackage);

        return $localPackage;
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
        $remotePackage = $this->packageSplitCartComponent->getCartPackageManager()->createNew();
        $remotePackage
            ->setCart($cart)
            ->setPackageType(PackageInterface::PACKAGE_TYPE_FROM_REMOTE_STOCK)
            ->setMaxShippingDays($maxShippingDays)
            ->setIsMerged($isMerged)
            ->setShippingForm($shippingForm);

        $cart->addCartPackage($remotePackage);

        return $remotePackage;
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
        $backorderPackage = $this->packageSplitCartComponent->getCartPackageManager()->createNew();
        $backorderPackage
            ->setCart($cart)
            ->setType(PackageInterface::PACKAGE_TYPE_BACKORDER)
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
     */
    public function getPackageCount(CartPackage $package): CartPackage
    {
        if (!$this->dataCartComponent->getPs()->get('cart.package.count')) {
            return $package;
        }

        $cart = $package->getCart();
        $user = $this->packageSplitCartComponent->getUser();

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
                $activePrice = $this->cartItemCartComponent->getPriceForCartItem($packageItem->getCartItem());
            }

            //increase total
            if ($this->dataCartComponent->getPs()->get('cart.calculation.gross')) {
                $valueNetto = $this->dataCartComponent->calculateMoneyNetValueFromGrossPrice($activePrice->getGrossPrice(true), $packageItem->getCartItem()->getQuantity(true), $activePrice->getVat(true));
                $valueGross = $this->dataCartComponent->calculateMoneyGrossValue($activePrice->getGrossPrice(true), $packageItem->getCartItem()->getQuantity(true));
                TaxManager::addMoneyValueToGrossRes($activePrice->getVat(true), $valueGross, $totalRes);
            } else {
                $valueNetto = $this->dataCartComponent->calculateMoneyNetValue($activePrice->getNetPrice(true), $packageItem->getCartItem()->getQuantity(true));
                $valueGross = $this->dataCartComponent->calculateMoneyGrossValueFromNetPrice($activePrice->getNetPrice(true), $packageItem->getCartItem()->getQuantity(true), $activePrice->getVat(true));
                TaxManager::addMoneyValueToNettoRes($activePrice->getVat(), $valueNetto, $totalRes);
            }

            $shippingDays = $packageItem->getShippingDays() > $shippingDays ? $packageItem->getShippingDays() : $shippingDays;
        }

        //zaokrąglamy na samym końcu
        if ($this->dataCartComponent->getPs()->get('cart.calculation.gross')) {
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
     * @param array $remoteQuantityWithStorages
     * @param array $daysHelper
     */
    protected function processSingleRemoteStorages(array $remoteQuantityWithStorages, array &$daysHelper)
    {
        foreach ($remoteQuantityWithStorages as $storageData) {
            $closest = $this->packageSplitCartComponent->getStorageService()->getClosest($storageData['shippingDays'], $daysHelper, true);

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
        $orderCode = $this->packageSplitCartComponent->isOrderCodeSet($cartItem->getOrderCode()) ? $cartItem->getOrderCode() : CartItem::DEFAULT_ORDER_CODE_VALUE;

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
            ValueHelper::convertToValue($availableRemotePackageQuantity),
            ValueHelper::convertToValue($backorderPackageQuantity),
            $maxShippingDaysForUserQuantity,
            $remoteQuantityWithStorages,
            ValueHelper::convertToValue($availableLocalPackageQuantity),
        ];
    }

    /**
     * @param CartInterface $cart
     * @return array
     * @throws \Exception
     */
    public function validate(CartInterface $cart): array
    {
        $errors = [];

        $user = $this->packageSplitCartComponent->getUser();

        /**
         * @var CartPackageInterface $cartPackage
         */
        foreach ($cart->getCartPackages() as $cartPackage) {
            if (!$cartPackage->getShippingMethod()) {
                $errors[] = $this->dataCartComponent->getTranslator()->trans('MissingShippingMethod', [], 'Cart');
            }
        }

        return $errors;
    }
}
