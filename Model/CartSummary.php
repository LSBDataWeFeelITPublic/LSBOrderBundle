<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Model;

use DateTime;
use LSB\UtilityBundle\Calculation\CalculationTypeInterface;
use LSB\UtilityBundle\Helper\ValueHelper;
use Money\Money;

class CartSummary
{

    const CALCULATION_TYPE_NET = 10;
    const CALCULATION_TYPE_GROSS = 20;

    protected int $cnt = 0;

    protected int $selectedCnt = 0;

    protected ?int $totalProductsNet = 0;

    protected ?int $totalProductsGross = 0;

    protected ?int $shippingCostNet = 0;

    protected ?int $shippingCostGross = 0;

    protected ?int $shippingCostFromNet = 0;

    protected ?int $shippingCostFromGross = 0;

    protected ?int $freeShippingThresholdNet = 0;

    protected ?int $freeShippingThresholdGross = 0;

    protected ?int $paymentCostNet = 0;

    protected ?int $paymentCostGross = 0;

    protected ?int $totalNet = 0;

    protected ?int $totalGross = 0;

    protected ?int $spreadNet = 0;

    protected ?int $spreadGross = 0;

    protected ?DateTime $calculatedAt = null;

    protected bool $showVatViesWarning = false;

    protected int $calculationType = CalculationTypeInterface::CALCULATION_TYPE_NET;

    protected ?string $currencyIsoCode;

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
        $additionalCosts = $this->totalNet - $this->totalProductsNet;

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
        if ($this->freeShippingThresholdNet) {
            $leftToFreeShipping = max($this->freeShippingThresholdNet - $this->totalProductsNet, 0);

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
     * @param bool $useMoney = false
     * @return Money|int|null
     */
    public function getTotalProductsNet(bool $useMoney = false): Money|int|null
    {
        return $useMoney ? ValueHelper::intToMoney($this->totalProductsNet, $this->currencyIsoCode) : $this->totalProductsNet;
    }

    /**
     * @param Money|int|null $totalProductsNet
     * @return CartSummary
     */
    public function setTotalProductsNet(Money|int|null $totalProductsNet): CartSummary
    {
        if ($totalProductsNet instanceof Money) {
            [$amount, $currency] = ValueHelper::moneyToIntCurrency($totalProductsNet);
            $this->totalProductsNet = $amount;
            $this->currencyIsoCode = $currency;
            return $this;
        }

        $this->totalProductsNet = $totalProductsNet;
        return $this;
    }

    /**
     * @param bool $useMoney = false
     * @return Money|int|null
     */
    public function getTotalProductsGross(bool $useMoney = false): Money|int|null
    {
        return $useMoney ? ValueHelper::intToMoney($this->totalProductsGross, $this->currencyIsoCode) : $this->totalProductsGross;
    }

    /**
     * @param Money|int|null $totalProductsGross
     * @return CartSummary
     */
    public function setTotalProductsGross(Money|int|null $totalProductsGross): CartSummary
    {
        if ($totalProductsGross instanceof Money) {
            [$amount, $currency] = ValueHelper::moneyToIntCurrency($totalProductsGross);
            $this->totalProductsGross = $amount;
            $this->currencyIsoCode = $currency;
            return $this;
        }

        $this->totalProductsGross = $totalProductsGross;
        return $this;
    }

    /**
     * @param bool $useMoney = false
     * @return Money|int|null
     */
    public function getShippingCostNet(bool $useMoney = false): Money|int|null
    {
        return $useMoney ? ValueHelper::intToMoney($this->shippingCostNet, $this->currencyIsoCode) : $this->shippingCostNet;
    }

    /**
     * @param Money|int|null $shippingCostNet
     * @return CartSummary
     */
    public function setShippingCostNet(Money|int|null $shippingCostNet): CartSummary
    {
        if ($shippingCostNet instanceof Money) {
            [$amount, $currency] = ValueHelper::moneyToIntCurrency($shippingCostNet);
            $this->shippingCostNet = $amount;
            $this->currencyIsoCode = $currency;
            return $this;
        }

        $this->shippingCostNet = $shippingCostNet;
        return $this;
    }

    /**
     * @param bool $useMoney = false
     * @return Money|int|null
     */
    public function getShippingCostGross(bool $useMoney = false): Money|int|null
    {
        return $useMoney ? ValueHelper::intToMoney($this->shippingCostGross, $this->currencyIsoCode) : $this->shippingCostGross;
    }

    /**
     * @param Money|int|null $shippingCostGross
     * @return CartSummary
     */
    public function setShippingCostGross(Money|int|null $shippingCostGross): CartSummary
    {
        if ($shippingCostGross instanceof Money) {
            [$amount, $currency] = ValueHelper::moneyToIntCurrency($shippingCostGross);
            $this->shippingCostGross = $amount;
            $this->currencyIsoCode = $currency;
            return $this;
        }

        $this->shippingCostGross = $shippingCostGross;
        return $this;
    }

    /**
     * @param bool $useMoney = false
     * @return Money|int|null
     */
    public function getShippingCostFromNet(bool $useMoney = false): Money|int|null
    {
        return $useMoney ? ValueHelper::intToMoney($this->shippingCostFromNet, $this->currencyIsoCode) : $this->shippingCostFromNet;
    }

    /**
     * @param Money|int|null $shippingCostFromNet
     * @return CartSummary
     */
    public function setShippingCostFromNet(Money|int|null $shippingCostFromNet): CartSummary
    {
        if ($shippingCostFromNet instanceof Money) {
            [$amount, $currency] = ValueHelper::moneyToIntCurrency($shippingCostFromNet);
            $this->shippingCostFromNet = $amount;
            $this->currencyIsoCode = $currency;
            return $this;
        }

        $this->shippingCostFromNet = $shippingCostFromNet;
        return $this;
    }

    /**
     * @param bool $useMoney = false
     * @return Money|int|null
     */
    public function getShippingCostFromGross(bool $useMoney = false): Money|int|null
    {
        return $useMoney ? ValueHelper::intToMoney($this->shippingCostFromGross, $this->currencyIsoCode) : $this->shippingCostFromGross;
    }

    /**
     * @param Money|int|null $shippingCostFromGross
     * @return CartSummary
     */
    public function setShippingCostFromGross(Money|int|null $shippingCostFromGross): CartSummary
    {
        if ($shippingCostFromGross instanceof Money) {
            [$amount, $currency] = ValueHelper::moneyToIntCurrency($shippingCostFromGross);
            $this->shippingCostFromGross = $amount;
            $this->currencyIsoCode = $currency;
            return $this;
        }

        $this->shippingCostFromGross = $shippingCostFromGross;
        return $this;
    }

    /**
     * @param bool $useMoney = false
     * @return Money|int|null
     */
    public function getFreeShippingThresholdNet(bool $useMoney = false): Money|int|null
    {
        return $useMoney ? ValueHelper::intToMoney($this->freeShippingThresholdNet, $this->currencyIsoCode) : $this->freeShippingThresholdNet;
    }

    /**
     * @param Money|int|null $freeShippingThresholdNet
     * @return CartSummary
     */
    public function setFreeShippingThresholdNet(Money|int|null  $freeShippingThresholdNet): CartSummary
    {
        if ($freeShippingThresholdNet instanceof Money) {
            [$amount, $currency] = ValueHelper::moneyToIntCurrency($freeShippingThresholdNet);
            $this->freeShippingThresholdNet = $amount;
            $this->currencyIsoCode = $currency;
            return $this;
        }

        $this->freeShippingThresholdNet = $freeShippingThresholdNet;
        return $this;
    }

    /**
     * @param bool $useMoney = false
     * @return Money|int|null
     */
    public function getFreeShippingThresholdGross(bool $useMoney = false): Money|int|null
    {
        return $useMoney ? ValueHelper::intToMoney($this->freeShippingThresholdNet, $this->currencyIsoCode) : $this->freeShippingThresholdNet;
    }

    /**
     * @param Money|int|null $freeShippingThresholdGross
     * @return CartSummary
     */
    public function setFreeShippingThresholdGross(Money|int|null $freeShippingThresholdGross): CartSummary
    {
        if ($freeShippingThresholdGross instanceof Money) {
            [$amount, $currency] = ValueHelper::moneyToIntCurrency($freeShippingThresholdGross);
            $this->freeShippingThresholdGross = $amount;
            $this->currencyIsoCode = $currency;
            return $this;
        }

        $this->freeShippingThresholdGross = $freeShippingThresholdGross;
        return $this;
    }

    /**
     * @param bool $useMoney = false
     * @return Money|int|null
     */
    public function getPaymentCostNet(bool $useMoney = false): Money|int|null
    {
        return $useMoney ? ValueHelper::intToMoney($this->paymentCostNet, $this->currencyIsoCode) : $this->paymentCostNet;
    }

    /**
     * @param Money|int|null $paymentCostNet
     * @return CartSummary
     */
    public function setPaymentCostNet(Money|int|null $paymentCostNet): CartSummary
    {
        if ($paymentCostNet instanceof Money) {
            [$amount, $currency] = ValueHelper::moneyToIntCurrency($paymentCostNet);
            $this->paymentCostNet = $amount;
            $this->currencyIsoCode = $currency;
            return $this;
        }

        $this->paymentCostNet = $paymentCostNet;
        return $this;
    }

    /**
     * @param bool $useMoney = false
     * @return int|null
     */
    public function getPaymentCostGross(bool $useMoney = false): Money|int|null
    {
        return $useMoney ? ValueHelper::intToMoney($this->paymentCostGross, $this->currencyIsoCode) : $this->paymentCostGross;
    }

    /**
     * @param Money|int|null $paymentCostGross
     * @return CartSummary
     */
    public function setPaymentCostGross(Money|int|null $paymentCostGross): CartSummary
    {
        if ($paymentCostGross instanceof Money) {
            [$amount, $currency] = ValueHelper::moneyToIntCurrency($paymentCostGross);
            $this->paymentCostGross = $amount;
            $this->currencyIsoCode = $currency;
            return $this;
        }

        $this->paymentCostGross = $paymentCostGross;
        return $this;
    }

    /**
     * @param bool $useMoney = false
     * @return Money|int|null
     */
    public function getTotalNet(bool $useMoney = false): Money|int|null
    {
        return $useMoney ? ValueHelper::intToMoney($this->totalNet, $this->currencyIsoCode) : $this->totalNet;
    }

    /**
     * @param Money|int|null $totalNet
     * @return CartSummary
     */
    public function setTotalNet(Money|int|null $totalNet): CartSummary
    {
        if ($totalNet instanceof Money) {
            [$amount, $currency] = ValueHelper::moneyToIntCurrency($totalNet);
            $this->totalNet = $amount;
            $this->currencyIsoCode = $currency;
            return $this;
        }

        $this->totalNet = $totalNet;
        return $this;
    }

    /**
     * @param bool $useMoney = false
     * @return Money|int|null
     */
    public function getTotalGross(bool $useMoney = false): Money|int|null
    {
        return $useMoney ? ValueHelper::intToMoney($this->totalGross, $this->currencyIsoCode) : $this->totalGross;
    }

    /**
     * @param Money|int|null $totalGross
     * @return CartSummary
     */
    public function setTotalGross(Money|int|null $totalGross): CartSummary
    {
        if ($totalGross instanceof Money) {
            [$amount, $currency] = ValueHelper::moneyToIntCurrency($totalGross);
            $this->totalGross = $amount;
            $this->currencyIsoCode = $currency;
            return $this;
        }

        $this->totalGross = $totalGross;
        return $this;
    }

    /**
     * @param bool $useMoney = false
     * @return Money|int|null
     */
    public function getSpreadNet(bool $useMoney = false): Money|int|null
    {
        return $useMoney ? ValueHelper::intToMoney($this->spreadNet, $this->currencyIsoCode) : $this->spreadNet;
    }

    /**
     * @param Money|int|null $spreadNet
     * @return CartSummary
     */
    public function setSpreadNet(Money|int|null $spreadNet): CartSummary
    {
        if ($spreadNet instanceof Money) {
            [$amount, $currency] = ValueHelper::moneyToIntCurrency($spreadNet);
            $this->spreadNet = $amount;
            $this->currencyIsoCode = $currency;
            return $this;
        }

        $this->spreadNet = $spreadNet;
        return $this;
    }

    /**
     * @param bool $useMoney = false
     * @return Money|int|null
     */
    public function getSpreadGross(bool $useMoney = false): Money|int|null
    {
        return $useMoney ? ValueHelper::intToMoney($this->spreadGross, $this->currencyIsoCode) : $this->spreadGross;
    }

    /**
     * @param Money|int|null $spreadGross
     * @return CartSummary
     */
    public function setSpreadGross(Money|int|null $spreadGross): CartSummary
    {
        if ($spreadGross instanceof Money) {
            [$amount, $currency] = ValueHelper::moneyToIntCurrency($spreadGross);
            $this->spreadGross = $amount;
            $this->currencyIsoCode = $currency;
            return $this;
        }

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
    public function getCurrencyIsoCode(): ?string
    {
        return $this->currencyIsoCode;
    }

    /**
     * @param string|null $currencyIsoCode
     * @return CartSummary
     */
    public function setCurrencyIsoCode(?string $currencyIsoCode): CartSummary
    {
        $this->currencyIsoCode = $currencyIsoCode;
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
