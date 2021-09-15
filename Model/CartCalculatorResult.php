<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Model;

use LSB\UtilityBundle\Attribute\Serialize;
use LSB\UtilityBundle\Value\Value;
use Money\Money;

/**
 * Class CartCalculatorResult
 * @package LSB\OrderBundle\Model
 */
#[Serialize]
class CartCalculatorResult
{
    /**
     * CartCalculatorResult constructor.
     * @param Money|null $priceNet
     * @param Money|null $priceGross
     * @param Value|null $taxPercentage
     * @param Value|null $calculationQuantity
     */
    public function __construct(
        protected ?Money $priceNet,
        protected ?Money $priceGross,
        protected ?Value $taxPercentage,
        protected ?Value $calculationQuantity
    ) {}

    /**
     * @return Money|null
     */
    public function getPriceNet(): ?Money
    {
        return $this->priceNet;
    }

    /**
     * @param Money|null $priceNet
     * @return $this
     */
    public function setPriceNet(?Money $priceNet): static
    {
        $this->priceNet = $priceNet;
        return $this;
    }

    /**
     * @return Money|null
     */
    public function getPriceGross(): ?Money
    {
        return $this->priceGross;
    }

    /**
     * @param Money|null $priceGross
     * @return $this
     */
    public function setPriceGross(?Money $priceGross): static
    {
        $this->priceGross = $priceGross;
        return $this;
    }

    /**
     * @return Value|null
     */
    public function getTaxPercentage(): ?Value
    {
        return $this->taxPercentage;
    }

    /**
     * @param Value|null $taxPercentage
     * @return $this
     */
    public function setTaxPercentage(?Value $taxPercentage): static
    {
        $this->taxPercentage = $taxPercentage;
        return $this;
    }

    /**
     * @return Value|null
     */
    public function getCalculationQuantity(): ?Value
    {
        return $this->calculationQuantity;
    }

    /**
     * @param Value|null $calculationQuantity
     * @return $this
     */
    public function setCalculationQuantity(?Value $calculationQuantity): static
    {
        $this->calculationQuantity = $calculationQuantity;
        return $this;
    }
}