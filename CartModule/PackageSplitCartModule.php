<?php
declare(strict_types=1);

namespace LSB\OrderBundle\CartModule;

use JetBrains\PhpStorm\Pure;
use LSB\LocaleBundle\Manager\TaxManager;
use LSB\OrderBundle\CartComponent\CartItemCartComponent;
use LSB\OrderBundle\CartComponent\DataCartComponent;
use LSB\OrderBundle\CartComponent\PackageSplitCartComponent;
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
        CartManager $cartManager,
        DataCartComponent                   $dataCartComponent,
        protected PackageSplitCartComponent $packageSplitCartComponent,
        protected CartItemCartComponent     $cartItemCartComponent,
        protected QuantityHelper $quantityHelper,
        protected PriceHelper $priceHelper
    ) {
        parent::__construct(
            $cartManager,
            $dataCartComponent
        );
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
     * Aktualizuje zawartość paczek na podstawie aktualnego stanu koszyka
     *
     * @param CartInterface $cart
     * @param bool $splitSupplier
     * @return bool|null
     * @throws \Exception
     */
    public function updatePackages(CartInterface $cart, bool $splitSupplier = false): ?bool
    {
        return $this->packageSplitCartComponent->updatePackages($cart, $splitSupplier);
    }

    /**
     * @param CartInterface|null $cart
     * @return bool
     * @throws \Exception
     */
    public function checkForDefaultCartOverSaleType(CartInterface $cart = null): bool
    {
        return $this->packageSplitCartComponent->checkForDefaultCartOverSaleType($cart);
    }

    /**
     * @param CartInterface $cart
     * @param bool $flush
     */
    public function splitPackagesForSupplier(CartInterface $cart, bool $flush = true): void
    {
        $this->splitPackagesForSupplier($cart, $flush);
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
