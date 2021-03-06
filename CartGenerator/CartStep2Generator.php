<?php
declare(strict_types=1);

namespace LSB\OrderBundle\CartGenerator;

use Doctrine\ORM\EntityManagerInterface;
use LSB\OrderBundle\CartModule\DataCartModule;
use LSB\OrderBundle\Entity\CartInterface;
use LSB\OrderBundle\Service\CartConverterService;
use LSB\OrderBundle\Service\CartModuleService;
use LSB\OrderBundle\Service\CartService;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class CartStep2Generator extends BaseCartStepGenerator
{
    const STEP = CartInterface::CART_STEP_2;

    const CODE = "converter";

    public function __construct(
        CartModuleService $moduleService,
        CartService $cartManager,
        EntityManagerInterface $em,
        CartConverterService $cartConverter,
        RequestStack $requestStack,
        EventDispatcherInterface $eventDispatcher
    ) {
        parent::__construct(
            $moduleService,
            $cartManager,
            $em,
            $cartConverter,
            $requestStack,
            $eventDispatcher
        );

        $this->isCartConverterStep = true;
        $this->previousStep = CartStep1Generator::STEP;
        $this->nextStep = null;
    }

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
     * @inheritdoc
     */
    public function prepare(): void
    {
        $this->cartManager->rebuildCart($this->cart);
        parent::prepare();
    }
}
