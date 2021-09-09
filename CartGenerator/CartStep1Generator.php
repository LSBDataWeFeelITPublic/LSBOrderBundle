<?php
declare(strict_types=1);

namespace LSB\OrderBundle\CartGenerator;

use LSB\OrderBundle\CartModule\CartItemCartModule;
use LSB\OrderBundle\CartModule\DataCartModule;
use LSB\OrderBundle\CartModule\PackageShippingCartModule;
use LSB\OrderBundle\CartModule\PackageSplitCartModule;
use LSB\OrderBundle\CartModule\PaymentCartModule;
use LSB\OrderBundle\Entity\CartInterface;

class CartStep1Generator extends BaseCartStepGenerator
{
    const STEP = CartInterface::CART_STEP_1;

    const CODE = "items";

    /**
     * @var int|null
     */
    protected ?int $nextStep = null;

    /**
     * @var int|null
     */
    protected ?int $previousStep = null;

    /**
     * @inheritDoc
     */
    public function getModuleList(): array
    {
        return [
            CartItemCartModule::NAME,
            PackageSplitCartModule::NAME,
            PackageShippingCartModule::NAME,
            PaymentCartModule::NAME,
            DataCartModule::NAME
        ];
    }

    /**
     * @param CartInterface|null $cart
     * @return array
     */
    public function isAccessible(?CartInterface $cart = null): array
    {
        $cart = $cart ?? $this->cart;
        return [$cart ? $cart->getCartItems()->count() > 0 : true, null];
    }

    /**
     * @inheritdoc
     */
    public function prepare(): void
    {
        //Po 1 requescie - sprawdzenie ceny produktu
        //TODO przygotować mechanizm w ramach koszyka
        //$this->cartManager->rebuildCart($this->cart);
        parent::prepare();
    }
}
