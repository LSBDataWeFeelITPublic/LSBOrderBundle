<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Model;

use DateTime;
use JMS\Serializer\Annotation\Groups;
use LSB\UtilityBundle\Calculation\CalculationTypeInterface;

/**
 * Class CartSummary
 * @package LSB\OrderBundle\Model
 */
class CartSummary
{

    const CALCULATION_TYPE_NET = 10;
    const CALCULATION_TYPE_GROSS = 20;

    /**
     * @var int
     */
    protected int $cnt = 0;

    /**
     * @var int
     */
    protected int $selectedCnt = 0;

    /**
     * @var float
     */
    protected float $totalProductsNetto = 0;

    /**
     * @var float
     */
    protected float $totalProductsGross = 0;

    /**
     * @var float
     */
    protected float $shippingCostNetto = 0;

    /**
     * @var float
     *
     * @Groups({"Default", "EDI_User", "EDI_Price", "SHOP_Public"})
     */
    protected float $shippingCostGross = 0;

    /**
     * @var float
     */
    protected float $shippingCostFromNetto = 0;

    /**
     * @var float
     */
    protected float $shippingCostFromGross = 0;

    /**
     * @var float
     */
    protected float $freeShippingThresholdNetto = 0;

    /**
     * @var float
     */
    protected float $freeShippingThresholdGross = 0;

    /**
     * @var float
     */
    protected float $paymentCostNetto = 0;

    /**
     * @var float
     */
    protected float $paymentCostGross = 0;

    /**
     * @var float
     */
    protected float $totalNetto = 0;

    /**
     * @var float
     */
    protected float $totalGross = 0;

    /**
     * @var float
     */
    protected float $spreadNetto = 0;

    /**
     * @var float
     */
    protected float $spreadGross = 0;

    /**
     * @var DateTime|null
     */
    protected ?DateTime $calculatedAt = null;

    /**
     * @var bool
     */
    protected bool $showVatViesWarning = false;

    /**
     * @var int
     */
    protected int $calculationType = CalculationTypeInterface::CALCULATION_TYPE_NET;

    /**
     * Oznaczenie waluty
     *
     * @var string|null
     */
    protected ?string $currencyCode;

    /**
     * Wyświetlanie cen w koszyku
     *
     * @var boolean
     */
    protected bool $showPrices = true;

    /**
     * @return bool
     */
    public function isCalculated(): bool
    {
        if ($this->calculatedAt) {
            return true;
        }

        return false;
    }

    /**
     * @param bool $round
     * @param int $precision
     * @return float
     */
    public function getTotalAdditionalCostsNet(bool $round = true, int $precision = 2): float
    {
        $additionalCosts = $this->totalNetto - $this->totalProductsNetto;

        if ($additionalCosts < 0) {
            $additionalCosts = 0;
        }

        return $round ? round($additionalCosts, $precision) : $additionalCosts;
    }

    /**
     * @param bool $round
     * @param int $precision
     * @return float
     */
    public function getTotalAdditionalCostsGross(bool $round = true, int $precision = 2): float
    {
        $additionalCosts = $this->totalGross - $this->totalProductsGross;

        if ($additionalCosts < 0) {
            $additionalCosts = 0;
        }

        return $round ? round($additionalCosts, $precision) : $additionalCosts;
    }

    /**
     * @param bool $round
     * @param int $precision
     * @return float|null
     */
    public function getLeftToFreeShippingNet(bool $round = true, int $precision = 2): ?float
    {
        if ($this->freeShippingThresholdNetto) {
            $leftToFreeShipping = max($this->freeShippingThresholdNetto - $this->totalProductsNetto, 0);

            return $round ? round($leftToFreeShipping, $precision) : $leftToFreeShipping;
        }

        return null;
    }

    /**
     * @param bool $round
     * @param int $precision
     * @return float|null
     */
    public function getLeftToFreeShippingGross(bool $round = true, int $precision = 2): ?float
    {
        if ($this->freeShippingThresholdGross) {
            $leftToFreeShipping = max($this->freeShippingThresholdGross - $this->totalProductsGross, 0);

            return $round ? round($leftToFreeShipping, $precision) : $leftToFreeShipping;
        }

        return null;
    }

    /**
     * @return int
     */
    public function getCnt(): int
    {
        return $this->cnt;
    }

    /**
     * @param int $cnt
     * @return CartSummary
     */
    public function setCnt(int $cnt): CartSummary
    {
        $this->cnt = $cnt;
        return $this;
    }

    /**
     * @return int
     */
    public function getSelectedCnt(): int
    {
        return $this->selectedCnt;
    }

    /**
     * @param int $selectedCnt
     * @return CartSummary
     */
    public function setSelectedCnt(int $selectedCnt): CartSummary
    {
        $this->selectedCnt = $selectedCnt;
        return $this;
    }

    /**
     * @return float
     */
    public function getTotalProductsNetto(): float|int
    {
        return $this->totalProductsNetto;
    }

    /**
     * @param float $totalProductsNetto
     * @return CartSummary
     */
    public function setTotalProductsNetto(float|int $totalProductsNetto): CartSummary
    {
        $this->totalProductsNetto = $totalProductsNetto;
        return $this;
    }

    /**
     * @return float
     */
    public function getTotalProductsGross(): float|int
    {
        return $this->totalProductsGross;
    }

    /**
     * @param float $totalProductsGross
     * @return CartSummary
     */
    public function setTotalProductsGross(float|int $totalProductsGross): CartSummary
    {
        $this->totalProductsGross = $totalProductsGross;
        return $this;
    }

    /**
     * @return float
     */
    public function getShippingCostNetto(): float|int
    {
        return $this->shippingCostNetto;
    }

    /**
     * @param float $shippingCostNetto
     * @return CartSummary
     */
    public function setShippingCostNetto(float|int $shippingCostNetto): CartSummary
    {
        $this->shippingCostNetto = $shippingCostNetto;
        return $this;
    }

    /**
     * @return float
     */
    public function getShippingCostGross(): float|int
    {
        return $this->shippingCostGross;
    }

    /**
     * @param float $shippingCostGross
     * @return CartSummary
     */
    public function setShippingCostGross(float|int $shippingCostGross): CartSummary
    {
        $this->shippingCostGross = $shippingCostGross;
        return $this;
    }

    /**
     * @return float
     */
    public function getShippingCostFromNetto(): float|int
    {
        return $this->shippingCostFromNetto;
    }

    /**
     * @param float $shippingCostFromNetto
     * @return CartSummary
     */
    public function setShippingCostFromNetto(float|int $shippingCostFromNetto): CartSummary
    {
        $this->shippingCostFromNetto = $shippingCostFromNetto;
        return $this;
    }

    /**
     * @return float
     */
    public function getShippingCostFromGross(): float|int
    {
        return $this->shippingCostFromGross;
    }

    /**
     * @param float $shippingCostFromGross
     * @return CartSummary
     */
    public function setShippingCostFromGross(float|int $shippingCostFromGross): CartSummary
    {
        $this->shippingCostFromGross = $shippingCostFromGross;
        return $this;
    }

    /**
     * @return float
     */
    public function getFreeShippingThresholdNetto(): float|int
    {
        return $this->freeShippingThresholdNetto;
    }

    /**
     * @param float $freeShippingThresholdNetto
     * @return CartSummary
     */
    public function setFreeShippingThresholdNetto(float|int $freeShippingThresholdNetto): CartSummary
    {
        $this->freeShippingThresholdNetto = $freeShippingThresholdNetto;
        return $this;
    }

    /**
     * @return float
     */
    public function getFreeShippingThresholdGross(): float|int
    {
        return $this->freeShippingThresholdGross;
    }

    /**
     * @param float $freeShippingThresholdGross
     * @return CartSummary
     */
    public function setFreeShippingThresholdGross(float|int $freeShippingThresholdGross): CartSummary
    {
        $this->freeShippingThresholdGross = $freeShippingThresholdGross;
        return $this;
    }

    /**
     * @return float
     */
    public function getPaymentCostNetto(): float|int
    {
        return $this->paymentCostNetto;
    }

    /**
     * @param float $paymentCostNetto
     * @return CartSummary
     */
    public function setPaymentCostNetto(float|int $paymentCostNetto): CartSummary
    {
        $this->paymentCostNetto = $paymentCostNetto;
        return $this;
    }

    /**
     * @return float
     */
    public function getPaymentCostGross(): float|int
    {
        return $this->paymentCostGross;
    }

    /**
     * @param float $paymentCostGross
     * @return CartSummary
     */
    public function setPaymentCostGross(float|int $paymentCostGross): CartSummary
    {
        $this->paymentCostGross = $paymentCostGross;
        return $this;
    }

    /**
     * @return float
     */
    public function getTotalNetto(): float|int
    {
        return $this->totalNetto;
    }

    /**
     * @param float $totalNetto
     * @return CartSummary
     */
    public function setTotalNetto(float|int $totalNetto): CartSummary
    {
        $this->totalNetto = $totalNetto;
        return $this;
    }

    /**
     * @return float
     */
    public function getTotalGross(): float|int
    {
        return $this->totalGross;
    }

    /**
     * @param float $totalGross
     * @return CartSummary
     */
    public function setTotalGross(float|int $totalGross): CartSummary
    {
        $this->totalGross = $totalGross;
        return $this;
    }

    /**
     * @return float
     */
    public function getSpreadNetto(): float|int
    {
        return $this->spreadNetto;
    }

    /**
     * @param float $spreadNetto
     * @return CartSummary
     */
    public function setSpreadNetto(float|int $spreadNetto): CartSummary
    {
        $this->spreadNetto = $spreadNetto;
        return $this;
    }

    /**
     * @return float
     */
    public function getSpreadGross(): float|int
    {
        return $this->spreadGross;
    }

    /**
     * @param float $spreadGross
     * @return CartSummary
     */
    public function setSpreadGross(float|int $spreadGross): CartSummary
    {
        $this->spreadGross = $spreadGross;
        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getCalculatedAt(): ?DateTime
    {
        return $this->calculatedAt;
    }

    /**
     * @param DateTime|null $calculatedAt
     * @return CartSummary
     */
    public function setCalculatedAt(?DateTime $calculatedAt): CartSummary
    {
        $this->calculatedAt = $calculatedAt;
        return $this;
    }

    /**
     * @return bool
     */
    public function isShowVatViesWarning(): bool
    {
        return $this->showVatViesWarning;
    }

    /**
     * @param bool $showVatViesWarning
     * @return CartSummary
     */
    public function setShowVatViesWarning(bool $showVatViesWarning): CartSummary
    {
        $this->showVatViesWarning = $showVatViesWarning;
        return $this;
    }

    /**
     * @return int
     */
    public function getCalculationType(): int
    {
        return $this->calculationType;
    }

    /**
     * @param int $calculationType
     * @return CartSummary
     */
    public function setCalculationType(int $calculationType): CartSummary
    {
        $this->calculationType = $calculationType;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCurrencyCode(): ?string
    {
        return $this->currencyCode;
    }

    /**
     * @param string|null $currencyCode
     * @return CartSummary
     */
    public function setCurrencyCode(?string $currencyCode): CartSummary
    {
        $this->currencyCode = $currencyCode;
        return $this;
    }

    /**
     * @return bool
     */
    public function isShowPrices(): bool
    {
        return $this->showPrices;
    }

    /**
     * @param bool $showPrices
     * @return CartSummary
     */
    public function setShowPrices(bool $showPrices): CartSummary
    {
        $this->showPrices = $showPrices;
        return $this;
    }
}
