<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Model;

use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\VirtualProperty;
use LSB\CustomerBundle\Interfaces\PriceTypeInterface;
use JMS\Serializer\Annotation\Groups;

/**
 * Class CartSummary
 * @package LSB\CartBundle\Model
 */
class CartSummary
{

    /**
     * @var integer
     *
     * @Groups({"Default", "EDI_User", "EDI_CartSummary", "EDI_CartHeaderSummary", "SHOP_Public", "SHOP_CartSummary"})
     */
    protected $cnt = 0;

    /**
     * @var integer
     *
     * @Groups({"Default", "EDI_User", "EDI_CartSummary", "EDI_CartHeaderSummary", "SHOP_Public", "SHOP_CartSummary"})
     *
     */
    protected $selectedCnt = 0;

    /**
     * @var float
     *
     * @Groups({"Default", "EDI_Price", "SHOP_Public"})
     */
    protected $totalProductsNetto = 0;

    /**
     * @var float
     *
     * @Groups({"Default", "EDI_User", "EDI_Price", "SHOP_Public"})
     */
    protected $totalProductsGross = 0;

    /**
     * @var float
     *
     * @Groups({"Default", "EDI_User", "EDI_Price", "SHOP_Public"})
     */
    protected $shippingCostNetto = 0;

    /**
     * @var float
     *
     * @Groups({"Default", "EDI_User", "EDI_Price", "SHOP_Public"})
     */
    protected $shippingCostGross = 0;

    /**
     * @var float
     *
     * @Groups({"Default", "EDI_User", "EDI_Price", "SHOP_Public"})
     */
    protected $shippingCostFromNetto = 0;

    /**
     * @var float
     *
     * @Groups({"Default", "EDI_User", "EDI_Price", "SHOP_Public"})
     */
    protected $shippingCostFromGross = 0;

    /**
     * @var float
     *
     * @Groups({"Default", "EDI_User", "EDI_Price", "SHOP_Public"})
     */
    protected $freeShippingThresholdNetto = 0;

    /**
     * @var float
     *
     * @Groups({"Default", "EDI_User", "EDI_Price", "SHOP_Public"})
     */
    protected $freeShippingThresholdGross = 0;

    /**
     * @var float
     *
     * @Groups({"Default", "EDI_User", "EDI_Price", "SHOP_Public"})
     */
    protected $paymentCostNetto = 0;

    /**
     * @var float
     *
     * @Groups({"Default", "EDI_User", "EDI_Price", "SHOP_Public"})
     */
    protected $paymentCostGross = 0;

    /**
     * @var float
     *
     * @Groups({"Default", "EDI_User", "EDI_Price", "SHOP_Public"})
     */
    protected $totalNetto = 0;

    /**
     * @var float
     *
     * @Groups({"Default", "EDI_User", "EDI_Price", "SHOP_Public"})
     */
    protected $totalGross = 0;

    /**
     * @var float
     */
    protected $spreadNetto = 0;

    /**
     * @var float
     */
    protected $spreadGross = 0;

    /**
     * @var \DateTime|null
     *
     * @Groups({"Default", "EDI_User", "EDI_CartSummary", "EDI_CartHeaderSummary", "SHOP_Public", "SHOP_CartSummary"})
     */
    protected $calculatedAt = null;

    /**
     * @var bool
     *
     * @Groups({"Default", "EDI_User", "EDI_CartSummary", "EDI_CartHeaderSummary", "SHOP_Public", "SHOP_CartSummary"})
     */
    protected $showVatViesWarning = false;

    /**
     * @var int
     *
     * @Groups({"Default", "EDI_User", "EDI_Price", "SHOP_Public"})
     */
    protected $calculationType = PriceTypeInterface::PRICE_TYPE_NETTO;

    /**
     * Oznaczenie waluty
     *
     * @var string|null
     * @Groups({"Default", "EDI_User", "EDI_Price", "SHOP_Public"})
     */
    protected $currencyCode;

    /**
     * Wyświetlanie cen w koszyku
     *
     * @var boolean
     */
    protected $showPrices = true;

    /**
     * @Groups({"Default", "EDI_User", "SHOP_Public"})
     *
     * Metoda określa, czy wartości zawarte w tym obiekcie mogą być brane pod uwagę
     *
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
     * Metoda wylicza sumę kosztów nie będących związanymi z produktami (netto)
     *
     * @VirtualProperty()
     * @SerializedName("totalAdditionalCostsNetto")
     * @Groups({"Default", "SHOP_Public"})
     *
     * @param bool $round
     * @param int $precision
     * @return float
     */
    public function getTotalAdditionalCostsNetto(bool $round = true, int $precision = 2): float
    {
        $additionalCosts = $this->totalNetto - $this->totalProductsNetto;

        if ($additionalCosts < 0) {
            $additionalCosts = 0;
        }

        return $round ? round($additionalCosts, $precision) : $additionalCosts;
    }

    /**
     * Metoda wylicza sumę kosztów nie będących związanymi z produktami (brutto)
     *
     * @VirtualProperty()
     * @SerializedName("totalAdditionalCostsGross")
     * @Groups({"Default", "SHOP_Public"})
     *
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
     * Pozostała kwota do darmowej dostawy (netto)
     *
     * @VirtualProperty()
     * @SerializedName("leftToFreeShippingNetto")
     * @Groups({"Default", "SHOP_Public"})
     *
     * @param bool $round
     * @param int $precision
     * @return float|null
     */
    public function getLeftToFreeShippingNetto(bool $round = true, int $precision = 2): ?float
    {
        if ($this->freeShippingThresholdNetto) {
            $leftToFreeShipping = max($this->freeShippingThresholdNetto - $this->totalProductsNetto, 0);

            return $round ? round($leftToFreeShipping, $precision) : $leftToFreeShipping;
        }

        return null;
    }

    /**
     * Pozostała kwota do darmowej dostawy (brutto)
     *
     * @VirtualProperty()
     * @SerializedName("leftToFreeShippingGross")
     * @Groups({"Default", "SHOP_Public"})
     *
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
    public function getTotalProductsNetto(): float
    {
        return $this->totalProductsNetto;
    }

    /**
     * @param float $totalProductsNetto
     * @return CartSummary
     */
    public function setTotalProductsNetto(float $totalProductsNetto): CartSummary
    {
        $this->totalProductsNetto = $totalProductsNetto;

        return $this;
    }

    /**
     * @return float
     */
    public function getTotalProductsGross(): float
    {
        return $this->totalProductsGross;
    }

    /**
     * @param float $totalProductsGross
     * @return CartSummary
     */
    public function setTotalProductsGross(float $totalProductsGross): CartSummary
    {
        $this->totalProductsGross = $totalProductsGross;

        return $this;
    }

    /**
     * @return float
     */
    public function getShippingCostNetto(): float
    {
        return $this->shippingCostNetto;
    }

    /**
     * @param float $shippingCostNetto
     * @return CartSummary
     */
    public function setShippingCostNetto(float $shippingCostNetto): CartSummary
    {
        $this->shippingCostNetto = $shippingCostNetto;

        return $this;
    }

    /**
     * @return float
     */
    public function getShippingCostGross(): float
    {
        return $this->shippingCostGross;
    }

    /**
     * @param float $shippingCostGross
     * @return CartSummary
     */
    public function setShippingCostGross(float $shippingCostGross): CartSummary
    {
        $this->shippingCostGross = $shippingCostGross;

        return $this;
    }

    /**
     * @return float
     */
    public function getPaymentCostNetto(): float
    {
        return $this->paymentCostNetto;
    }

    /**
     * @param float $paymentCostNetto
     * @return CartSummary
     */
    public function setPaymentCostNetto(float $paymentCostNetto): CartSummary
    {
        $this->paymentCostNetto = $paymentCostNetto;

        return $this;
    }

    /**
     * @return float
     */
    public function getPaymentCostGross(): float
    {
        return $this->paymentCostGross;
    }

    /**
     * @param float $paymentCostGross
     * @return CartSummary
     */
    public function setPaymentCostGross(float $paymentCostGross): CartSummary
    {
        $this->paymentCostGross = $paymentCostGross;

        return $this;
    }

    /**
     * @return float
     */
    public function getTotalNetto(): float
    {
        return $this->totalNetto;
    }

    /**
     * @param float $totalNetto
     * @return CartSummary
     */
    public function setTotalNetto(float $totalNetto): CartSummary
    {
        $this->totalNetto = $totalNetto;

        return $this;
    }

    /**
     * @return float
     */
    public function getTotalGross(): float
    {
        return $this->totalGross;
    }

    /**
     * @param float $totalGross
     * @return CartSummary
     */
    public function setTotalGross(float $totalGross): CartSummary
    {
        $this->totalGross = $totalGross;

        return $this;
    }

    /**
     * @return float
     */
    public function getSpreadNetto(): float
    {
        return $this->spreadNetto;
    }

    /**
     * @param float $spreadNetto
     * @return CartSummary
     */
    public function setSpreadNetto(float $spreadNetto): CartSummary
    {
        $this->spreadNetto = $spreadNetto;

        return $this;
    }

    /**
     * @return float
     */
    public function getSpreadGross(): float
    {
        return $this->spreadGross;
    }

    /**
     * @param float $spreadGross
     * @return CartSummary
     */
    public function setSpreadGross(float $spreadGross): CartSummary
    {
        $this->spreadGross = $spreadGross;

        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getCalculatedAt(): ?\DateTime
    {
        return $this->calculatedAt;
    }

    /**
     * @param \DateTime $calculatedAt
     * @return CartSummary
     */
    public function setCalculatedAt(\DateTime $calculatedAt): CartSummary
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
     * @return float|null
     */
    public function getShippingCostFromNetto(): ?float
    {
        return $this->shippingCostFromNetto;
    }

    /**
     * @param float $shippingCostFromNetto
     * @return CartSummary
     */
    public function setShippingCostFromNetto(float $shippingCostFromNetto): CartSummary
    {
        $this->shippingCostFromNetto = $shippingCostFromNetto;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getShippingCostFromGross(): ?float
    {
        return $this->shippingCostFromGross;
    }

    /**
     * @param float $shippingCostFromGross
     * @return CartSummary
     */
    public function setShippingCostFromGross(float $shippingCostFromGross): CartSummary
    {
        $this->shippingCostFromGross = $shippingCostFromGross;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getFreeShippingThresholdNetto(): ?float
    {
        return $this->freeShippingThresholdNetto;
    }

    /**
     * @param float $freeShippingThresholdNetto
     * @return CartSummary
     */
    public function setFreeShippingThresholdNetto(?float $freeShippingThresholdNetto): CartSummary
    {
        $this->freeShippingThresholdNetto = $freeShippingThresholdNetto;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getFreeShippingThresholdGross(): ?float
    {
        return $this->freeShippingThresholdGross;
    }

    /**
     * @param float $freeShippingThresholdGross
     * @return CartSummary
     */
    public function setFreeShippingThresholdGross(?float $freeShippingThresholdGross): CartSummary
    {
        $this->freeShippingThresholdGross = $freeShippingThresholdGross;
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
    public function getShowPrices(): bool
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
