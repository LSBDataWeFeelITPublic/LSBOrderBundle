<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Model;

/**
 * Class CartCalculatorResult
 * @package LSB\OrderBundle\Model
 */
class CartCalculatorResult
{
    /**
     * @var null|float
     */
    protected ?float $priceNet;

    /**
     * @var null|float
     */
    protected ?float $priceGross;

    /**
     * @var int|null
     */
    protected ?int $taxPercentage;

    /**
     * @var null|float
     */
    protected ?float $calculationQuantity;

    /**
     * CartCalculatorResult constructor.
     * @param float|null $priceNet
     * @param float|null $priceGross
     * @param int|null $taxPercentage
     * @param float|null $calculationQuantity
     */
    public function __construct(
        ?float $priceNet,
        ?float $priceGross,
        ?int $taxPercentage,
        ?float $calculationQuantity
    ) {
        $this->priceNet = $priceNet;
        $this->priceGross = $priceGross;
        $this->taxPercentage = $taxPercentage;
        $this->calculationQuantity = $calculationQuantity;
    }

    /**
     * @param bool $round
     * @param int $precision
     * @return float|null
     */
    public function getPriceNetto(bool $round = false, int $precision = 2): ?float
    {
        if ($this->priceNet) {
            return round($this->priceNet, $precision);
        }

        return $this->priceNet;
    }

    /**
     * @param bool $round
     * @param int $precision
     * @return float|null
     */
    public function getPriceGross(bool $round = false, int $precision = 2): ?float
    {
        if ($this->priceGross) {
            return round($this->priceGross, $precision);
        }

        return $this->priceGross;
    }

    /**
     * @return int|null
     */
    public function getTaxPercentage(): ?int
    {
        return $this->taxPercentage;
    }

    /**
     * @return float|null
     */
    public function getCalculationQuantity(): ?float
    {
        return $this->calculationQuantity;
    }

    /**
     * @param float|null $priceNet
     * @return CartCalculatorResult
     */
    public function setPriceNet(?float $priceNet): CartCalculatorResult
    {
        $this->priceNet = $priceNet;
        return $this;
    }

    /**
     * @param float|null $priceGross
     * @return CartCalculatorResult
     */
    public function setPriceGross(?float $priceGross): CartCalculatorResult
    {
        $this->priceGross = $priceGross;
        return $this;
    }

}