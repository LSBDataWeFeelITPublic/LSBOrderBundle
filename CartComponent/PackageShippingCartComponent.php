<?php
declare(strict_types=1);

namespace LSB\OrderBundle\CartComponent;

use LSB\OrderBundle\Manager\CartPackageItemManager;
use LSB\OrderBundle\Manager\CartPackageManager;
use LSB\OrderBundle\Service\CartCalculatorService;
use LSB\ProductBundle\Manager\SupplierManager;
use LSB\ProductBundle\Service\StorageService;
use LSB\ShippingBundle\Manager\MethodManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class PackageShippingCartComponent extends BaseCartComponent
{
    const NAME = 'packageShipping';

    public function __construct(
        TokenStorageInterface            $tokenStorage,
        protected CartPackageManager     $cartPackageManager,
        protected SupplierManager        $supplierManager,
        protected CartPackageItemManager $cartPackageItemManager,
        protected StorageService         $storageService,
        protected MethodManager          $shippingMethodManager,
        protected CartCalculatorService  $cartCalculatorService
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
     * @return MethodManager
     */
    public function getShippingMethodManager(): MethodManager
    {
        return $this->shippingMethodManager;
    }

    /**
     * @return CartCalculatorService
     */
    public function getCartCalculatorService(): CartCalculatorService
    {
        return $this->cartCalculatorService;
    }
}