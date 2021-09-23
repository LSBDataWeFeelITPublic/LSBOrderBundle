<?php
declare(strict_types=1);

namespace LSB\OrderBundle\CartGenerator;

use Doctrine\ORM\EntityManagerInterface;
use LSB\OrderBundle\CartModule\CartItemCartModule;
use LSB\OrderBundle\CartModule\DataCartModule;
use LSB\OrderBundle\CartModule\PackageShippingCartModule;
use LSB\OrderBundle\CartModule\PackageSplitCartModule;
use LSB\OrderBundle\CartModule\PaymentCartModule;
use LSB\OrderBundle\Entity\CartInterface;
use LSB\OrderBundle\Service\CartConverterService;
use LSB\OrderBundle\Service\CartModuleService;
use LSB\OrderBundle\Service\CartService;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class CartStep1Generator extends BaseCartStepGenerator
{
    const STEP = CartInterface::CART_STEP_1;

    const CODE = "items";

    public function __construct(
        CartModuleService $moduleService,
        CartService $cartManager,
        EntityManagerInterface $em,
        CartConverterService $cartConverter,
        RequestStack $requestStack,
        EventDispatcherInterface $eventDispatcher
    ) {
        parent::__construct($moduleService, $cartManager, $em, $cartConverter, $requestStack, $eventDispatcher);

        $this->nextStep = CartStep2Generator::STEP;
    }

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
        //Po 1 requescie - sprawdzenie ceny produktu
        //TODO przygotować mechanizm w ramach koszyka
        $this->cartManager->rebuildCart($this->cart);
        parent::prepare();
    }
}
