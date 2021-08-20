<?php

namespace LSB\OrderBundle\Service;

use LSB\OrderBundle\CartComponent\CartComponentInterface;

/**
 *
 */
class CartComponentService
{
    const CART_COMPONENT_TAG_NAME = 'cart.component';

    public function __construct(
        protected CartComponentInventory $cartComponentInventory
    ){}

    /**
     * @param $moduleName
     * @return CartComponentInterface|null
     * @throws \Exception
     */
    public function getComponentByClass($moduleName): ?CartComponentInterface
    {

        $component = $this->cartComponentInventory->getModuleByClass($moduleName);

        if ($component instanceof CartComponentInterface) {
            return $component;
        }

        return null;
    }
}