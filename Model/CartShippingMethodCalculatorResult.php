<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Model;

use LSB\OrderBundle\Entity\CartPackageInterface;
use LSB\ShippingBundle\Entity\MethodInterface;
use LSB\UtilityBundle\Attribute\Serialize;
use LSB\UtilityBundle\Value\Value;
use Money\Money;

/**
 * Class CartShippingFormCalculatorResult
 * @package LSB\OrderBundle\Model
 */
#[Serialize]
class CartShippingMethodCalculatorResult extends CartCalculatorResult
{
    public function __construct(
        ?Money                          $priceNet = null,
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
            $priceNet,
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
     * @return $this
     */
    public function setFreeDeliveryThresholdValueNet(?Money $freeDeliveryThresholdValueNet): static
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
     * @return $this
     */
    public function setFreeDeliveryThresholdValueGross(?Money $freeDeliveryThresholdValueGross): static
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
     * @return $this
     */
    public function setAverageUnitPriceNet(?Money $averageUnitPriceNet): static
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
     * @return $this
     */
    public function setAverageUnitPriceGross(?Money $averageUnitPriceGross): static
    {
        $this->averageUnitPriceGross = $averageUnitPriceGross;
        return $this;
    }

    /**
     * @return MethodInterface|null
     */
    public function getShippingMethod(): ?MethodInterface
    {
        return $this->shippingMethod;
    }

    /**
     * @param MethodInterface|null $shippingMethod
     * @return $this
     */
    public function setShippingMethod(?MethodInterface $shippingMethod): static
    {
        $this->shippingMethod = $shippingMethod;
        return $this;
    }

    /**
     * @return CartPackageInterface|null
     */
    public function getCartPackage(): ?CartPackageInterface
    {
        return $this->cartPackage;
    }

    /**
     * @param CartPackageInterface|null $cartPackage
     * @return $this
     */
    public function setCartPackage(?CartPackageInterface $cartPackage): static
    {
        $this->cartPackage = $cartPackage;
        return $this;
    }
}