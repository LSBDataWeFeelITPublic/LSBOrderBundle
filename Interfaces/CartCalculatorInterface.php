<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Interfaces;

use LSB\OrderBundle\Entity\CartInterface;
use LSB\OrderBundle\Model\CartCalculatorResult;
use LSB\UtilityBundle\ModuleInventory\ModuleInventoryInterface;

interface CartCalculatorInterface extends ModuleInventoryInterface
{
    /**
     * Returns the name of the module
     *
     * @return mixed
     */
    public function getModule(): string;

    /**
     * @param array $configurationData
     */
    public function setCalculationData(array $configurationData):void;

    /**
     * @return CartCalculatorResult|null
     */
    public function calculate(): ?CartCalculatorResult;

    /**
     * @param CartInterface $cart
     * @return CartCalculatorInterface
     */
    public function setCart(CartInterface $cart): CartCalculatorInterface;

    /**
     * @return void
     */
    public function clearCalculationData():void;
}