<?php
declare(strict_types=1);

namespace LSB\OrderBundle\CartComponent;

use LSB\OrderBundle\Entity\CartInterface;
use LSB\OrderBundle\Entity\CartItem;
use LSB\OrderBundle\Entity\CartItemInterface;
use LSB\OrderBundle\Entity\CartPackageInterface;
use LSB\OrderBundle\Entity\PackageItem;
use LSB\OrderBundle\Manager\CartManager;
use LSB\OrderBundle\Manager\CartPackageItemManager;
use LSB\OrderBundle\Manager\CartPackageManager;
use LSB\ProductBundle\Entity\ProductInterface;
use LSB\ProductBundle\Manager\SupplierManager;
use LSB\ProductBundle\Service\StorageService;
use LSB\UtilityBundle\Helper\ValueHelper;
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
        protected ParameterBagInterface  $ps
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
}