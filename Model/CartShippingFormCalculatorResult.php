<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Model;

use JMS\Serializer\Annotation\Groups;

/**
 * Class CartShippingFormCalulatorResult
 * @package LSB\CartBundle\Model
 */
class CartShippingFormCalculatorResult extends CartCalculatorResult
{
    /**
     * Wartość progu darmowej dostawy (netto)
     *
     * @Groups({"Default", "EDI_Price", "SHOP_Public"})
     * @var null|float
     */
    protected $freeDeliveryThresholdValueNetto;

    /**
     * Wartość progu darmowe dostawy (brutto)
     *
     * @Groups({"Default", "EDI_Price", "SHOP_Public"})
     * @var null|float
     */
    protected $freeDeliveryThresholdValueGross;

    /**
     * Średni koszt jednostkowy (netto)
     *
     * @Groups({"Default", "EDI_Price", "SHOP_Public"})
     * @var null|float
     */
    protected $averageUnitPriceNetto;

    /**
     * Średni koszt jednostkowy (brutto)
     *
     * @Groups({"Default", "EDI_Price", "SHOP_Public"})
     * @var null|float
     */
    protected $averageUnitPriceGross;

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
    public function getFreeDeliveryThresholdValueNetto(): ?float
    {
        return $this->freeDeliveryThresholdValueNetto;
    }

    /**
     * @return float|null
     */
    public function getFreeDeliveryThresholdValueGross(): ?float
    {
        return $this->freeDeliveryThresholdValueGross;
    }

    /**
     * @return float|null
     */
    public function getAverageUnitPriceNetto(): ?float
    {
        return $this->averageUnitPriceNetto;
    }

    /**
     * @return float|null
     */
    public function getAverageUnitPriceGross(): ?float
    {
        return $this->averageUnitPriceGross;
    }
}