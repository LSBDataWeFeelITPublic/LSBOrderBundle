<?php
declare(strict_types=1);

namespace LSB\OrderBundle\CartGenerator;

use LSB\OrderBundle\CartModule\CartItemCartModule;
use LSB\OrderBundle\CartModule\DataCartModule;
use LSB\OrderBundle\CartModule\PackageShippingCartModule;
use LSB\OrderBundle\CartModule\PackageSplitCartModule;
use LSB\OrderBundle\CartModule\PaymentCartModule;
use LSB\OrderBundle\Entity\CartInterface;

class CartStep2Generator extends BaseCartStepGenerator
{
    const STEP = CartInterface::CART_STEP_2;

    const CODE = "converter";

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
            DataCartModule::NAME
        ];
    }

    /**
     * @param CartInterface|null $cart
     * @return array
     */
    public function isAccessible(?CartInterface $cart = null): array
    {
        //FIXED
        return [true, null];

        $cart = $cart ?? $this->cart;
        return [$cart ? true : false, null];
    }

    /**
     * @inheritdoc
     */
    public function prepare(): void
    {
        //TODO przygotować mechanizm w ramach koszyka
        //$this->cartManager->rebuildCart($this->cart);
        parent::prepare();
    }
}
