<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Model;

use LSB\OrderBundle\Entity\CartPackageInterface;
use LSB\ShippingBundle\Entity\MethodInterface;
use LSB\UtilityBundle\Value\Value;
use Money\Money;

/**
 * Class CartShippingFormCalculatorResult
 * @package LSB\OrderBundle\Model
 */

class CartShippingMethodCalculatorResult extends CartCalculatorResult
{
    public function __construct(
        ?Money                          $priceNetto = null,
        ?Money                          $priceGross = null,
        ?Value                          $taxPercentage = null,
        ?Value                          $calculationQuantity = null,
        protected ?Money                $freeDeliveryThresholdValueNet = null,
        protected ?Money                $freeDeliveryThresholdValueGross = null,
        protected ?Money                $averageUnitPriceNet = null,
        protected ?Money                $averageUnitPriceGross = null,
        protected ?MethodInterface      $shippingMethod = null,
        protected ?CartPackageInterface $cartPackage = null
    ) {
        parent::__construct(
            $priceNetto,
            $priceGross,
            $taxPercentage,
            $calculationQuantity
        );
    }

    /**
     * @return Money|null
     */
    public function getFreeDeliveryThresholdValueNet(): ?Money
    {
        return $this->freeDeliveryThresholdValueNet;
    }

    /**
     * @param Money|null $freeDeliveryThresholdValueNet
     * @return CartShippingMethodCalculatorResult
     */
    public function setFreeDeliveryThresholdValueNet(?Money $freeDeliveryThresholdValueNet): CartShippingMethodCalculatorResult
    {
        $this->freeDeliveryThresholdValueNet = $freeDeliveryThresholdValueNet;
        return $this;
    }

    /**
     * @return Money|null
     */
    public function getFreeDeliveryThresholdValueGross(): ?Money
    {
        return $this->freeDeliveryThresholdValueGross;
    }

    /**
     * @param Money|null $freeDeliveryThresholdValueGross
     * @return CartShippingMethodCalculatorResult
     */
    public function setFreeDeliveryThresholdValueGross(?Money $freeDeliveryThresholdValueGross): CartShippingMethodCalculatorResult
    {
        $this->freeDeliveryThresholdValueGross = $freeDeliveryThresholdValueGross;
        return $this;
    }

    /**
     * @return Money|null
     */
    public function getAverageUnitPriceNet(): ?Money
    {
        return $this->averageUnitPriceNet;
    }

    /**
     * @param Money|null $averageUnitPriceNet
     * @return CartShippingMethodCalculatorResult
     */
    public function setAverageUnitPriceNet(?Money $averageUnitPriceNet): CartShippingMethodCalculatorResult
    {
        $this->averageUnitPriceNet = $averageUnitPriceNet;
        return $this;
    }

    /**
     * @return Money|null
     */
    public function getAverageUnitPriceGross(): ?Money
    {
        return $this->averageUnitPriceGross;
    }

    /**
     * @param Money|null $averageUnitPriceGross
     * @return CartShippingMethodCalculatorResult
     */
    public function setAverageUnitPriceGross(?Money $averageUnitPriceGross): CartShippingMethodCalculatorResult
    {
        $this->averageUnitPriceGross = $averageUnitPriceGross;
        return $this;
    }
}