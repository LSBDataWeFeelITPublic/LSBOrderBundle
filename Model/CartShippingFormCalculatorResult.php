<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Model;

/**
 * Class CartShippingFormCalculatorResult
 * @package LSB\OrderBundle\Model
 */
class CartShippingFormCalculatorResult extends CartCalculatorResult
{
    /**
     * @var null|float
     */
    protected ?float $freeDeliveryThresholdValueNetto;

    /**
     * @var null|float
     */
    protected ?float $freeDeliveryThresholdValueGross;

    /**
     * @var null|float
     */
    protected ?float $averageUnitPriceNetto;

    /**
     * @var null|float
     */
    protected ?float $averageUnitPriceGross;

    /**
     * CartShippingFormCalulatorResult constructor.
     * @param float|null $priceNetto
     * @param float|null $priceGross
     * @param int|null $taxPercentage
     * @param float|null $calculationQuantity
     * @param float|null $freeDeliveryThresholdValueNetto
     * @param float|null $freeDeliveryThresholdValueGross
     * @param float|null $averageUnitPriceNetto
     * @param float|null $averageUnitPriceGross
     */
    public function __construct(
        ?float $priceNetto,
        ?float $priceGross,
        ?int $taxPercentage,
        ?float $calculationQuantity,
        ?float $freeDeliveryThresholdValueNetto,
        ?float $freeDeliveryThresholdValueGross,
        ?float $averageUnitPriceNetto,
        ?float $averageUnitPriceGross
    ) {
        parent::__construct(
            $priceNetto,
            $priceGross,
            $taxPercentage,
            $calculationQuantity
        );

        $this->freeDeliveryThresholdValueNetto = $freeDeliveryThresholdValueNetto;
        $this->freeDeliveryThresholdValueGross = $freeDeliveryThresholdValueGross;
        $this->averageUnitPriceNetto = $averageUnitPriceNetto;
        $this->averageUnitPriceGross = $averageUnitPriceGross;
    }

    /**
     * @return float|null
     */
    public function getPriceNet(): ?float
    {
        return $this->priceNet;
    }

    /**
     * @param float|null $priceNet
     * @return CartShippingFormCalculatorResult
     */
    public function setPriceNet(?float $priceNet): CartShippingFormCalculatorResult
    {
        $this->priceNet = $priceNet;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getPriceGross(): ?float
    {
        return $this->priceGross;
    }

    /**
     * @param float|null $priceGross
     * @return CartShippingFormCalculatorResult
     */
    public function setPriceGross(?float $priceGross): CartShippingFormCalculatorResult
    {
        $this->priceGross = $priceGross;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getTaxPercentage(): ?int
    {
        return $this->taxPercentage;
    }

    /**
     * @param int|null $taxPercentage
     * @return CartShippingFormCalculatorResult
     */
    public function setTaxPercentage(?int $taxPercentage): CartShippingFormCalculatorResult
    {
        $this->taxPercentage = $taxPercentage;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getCalculationQuantity(): ?float
    {
        return $this->calculationQuantity;
    }

    /**
     * @param float|null $calculationQuantity
     * @return CartShippingFormCalculatorResult
     */
    public function setCalculationQuantity(?float $calculationQuantity): CartShippingFormCalculatorResult
    {
        $this->calculationQuantity = $calculationQuantity;
        return $this;
    }


}