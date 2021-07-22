<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Model;

use DateTime;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Groups;
use LSB\ProductBundle\Model\Price;

/**
 * Class CartItemSummary
 * @package LSB\CartBundle\Model
 */
class CartItemSummary
{
    /**
     * @var float|null
     */
    protected ?float $priceNetto = null;

    /**
     * @var float|null
     */
    protected ?float $priceGross = null;

    /**
     * @var float|null
     */
    protected ?float $valueNetto = null;

    /**
     * @var float|null
     */
    protected ?float $valueGross = null;

    /**
     * @var float|null
     */
    protected ?float $basePriceNetto = null;

    /**
     * @var float|null
     */
    protected ?float $basePriceGross = null;

    /**
     * @var float|null
     */
    protected ?float $baseValueNetto = null;

    /**
     * @var float|null
     */
    protected ?float $baseValueGross = null;

    /**
     * @var float|null
     */
    protected ?float $taxValue = null;

    /**
     * @var int|null
     */
    protected ?int $taxRate = null;

    /**
     * @var array
     */
    protected array $res = [];

    /**
     * @var null|float
     */
    protected ?float $quantity;

    /**
     * @var DateTime|null
     */
    protected ?DateTime $calculatedAt;

    /**
     * Oznaczenie waluty
     *
     * @var string|null
     */
    protected ?string $currencyIsoCode;

    /**
     * Wyświetlanie cen w koszyku
     *
     * @var boolean
     */
    protected bool $showPrices = true;

    /**
     * @var bool
     */
    protected bool $isProductSet = false;

    /**
     * W przypadku zestawów należy przechowywać pulę obiektów;
     *
     * @var array
     */
    protected array $productSetProductActivePrices = [];

    /**
     * @return float|null
     */
    public function getPriceNetto(): ?float
    {
        return $this->priceNetto;
    }

    /**
     * @param float|null $priceNetto
     * @return CartItemSummary
     */
    public function setPriceNetto(?float $priceNetto): CartItemSummary
    {
        $this->priceNetto = $priceNetto;
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
     * @return CartItemSummary
     */
    public function setPriceGross(?float $priceGross): CartItemSummary
    {
        $this->priceGross = $priceGross;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getValueNetto(): ?float
    {
        return $this->valueNetto;
    }

    /**
     * @param float|null $valueNetto
     * @return CartItemSummary
     */
    public function setValueNetto(?float $valueNetto): CartItemSummary
    {
        $this->valueNetto = $valueNetto;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getValueGross(): ?float
    {
        return $this->valueGross;
    }

    /**
     * @param float|null $valueGross
     * @return CartItemSummary
     */
    public function setValueGross(?float $valueGross): CartItemSummary
    {
        $this->valueGross = $valueGross;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getBasePriceNetto(): ?float
    {
        return $this->basePriceNetto;
    }

    /**
     * @param float|null $basePriceNetto
     * @return CartItemSummary
     */
    public function setBasePriceNetto(?float $basePriceNetto): CartItemSummary
    {
        $this->basePriceNetto = $basePriceNetto;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getBasePriceGross(): ?float
    {
        return $this->basePriceGross;
    }

    /**
     * @param float|null $basePriceGross
     * @return CartItemSummary
     */
    public function setBasePriceGross(?float $basePriceGross): CartItemSummary
    {
        $this->basePriceGross = $basePriceGross;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getBaseValueNetto(): ?float
    {
        return $this->baseValueNetto;
    }

    /**
     * @param float|null $baseValueNetto
     * @return CartItemSummary
     */
    public function setBaseValueNetto(?float $baseValueNetto): CartItemSummary
    {
        $this->baseValueNetto = $baseValueNetto;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getBaseValueGross(): ?float
    {
        return $this->baseValueGross;
    }

    /**
     * @param float|null $baseValueGross
     * @return CartItemSummary
     */
    public function setBaseValueGross(?float $baseValueGross): CartItemSummary
    {
        $this->baseValueGross = $baseValueGross;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getTaxValue(): ?float
    {
        return $this->taxValue;
    }

    /**
     * @param float|null $taxValue
     * @return CartItemSummary
     */
    public function setTaxValue(?float $taxValue): CartItemSummary
    {
        $this->taxValue = $taxValue;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getTaxRate(): ?int
    {
        return $this->taxRate;
    }

    /**
     * @param int|null $taxRate
     * @return CartItemSummary
     */
    public function setTaxRate(?int $taxRate): CartItemSummary
    {
        $this->taxRate = $taxRate;
        return $this;
    }

    /**
     * @return array
     */
    public function getRes(): array
    {
        return $this->res;
    }

    /**
     * @param ${ENTRY_HINT} $re
     *
     * @return CartItemSummary
     */
    public function addRe($re): CartItemSummary
    {
        if (false === in_array($re, $this->res, true)) {
            $this->res[] = $re;
        }
        return $this;
    }

    /**
     * @param ${ENTRY_HINT} $re
     *
     * @return CartItemSummary
     */
    public function removeRe($re): CartItemSummary
    {
        if (true === in_array($re, $this->res, true)) {
            $index = array_search($re, $this->res);
            array_splice($this->res, $index, 1);
        }
        return $this;
    }

    /**
     * @param array $res
     * @return CartItemSummary
     */
    public function setRes(array $res): CartItemSummary
    {
        $this->res = $res;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getQuantity(): ?float
    {
        return $this->quantity;
    }

    /**
     * @param float|null $quantity
     * @return CartItemSummary
     */
    public function setQuantity(?float $quantity): CartItemSummary
    {
        $this->quantity = $quantity;
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
     * @return CartItemSummary
     */
    public function setCalculatedAt(?DateTime $calculatedAt): CartItemSummary
    {
        $this->calculatedAt = $calculatedAt;
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
     * @return CartItemSummary
     */
    public function setCurrencyIsoCode(?string $currencyIsoCode): CartItemSummary
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
     * @return CartItemSummary
     */
    public function setShowPrices(bool $showPrices): CartItemSummary
    {
        $this->showPrices = $showPrices;
        return $this;
    }

    /**
     * @return bool
     */
    public function isProductSet(): bool
    {
        return $this->isProductSet;
    }

    /**
     * @param bool $isProductSet
     * @return CartItemSummary
     */
    public function setIsProductSet(bool $isProductSet): CartItemSummary
    {
        $this->isProductSet = $isProductSet;
        return $this;
    }

    /**
     * @return array
     */
    public function getProductSetProductActivePrices(): array
    {
        return $this->productSetProductActivePrices;
    }

    /**
     * @param ${ENTRY_HINT} $productSetProductActivePrice
     *
     * @return CartItemSummary
     */
    public function addProductSetProductActivePrice($productSetProductActivePrice): CartItemSummary
    {
        if (false === in_array($productSetProductActivePrice, $this->productSetProductActivePrices, true)) {
            $this->productSetProductActivePrices[] = $productSetProductActivePrice;
        }
        return $this;
    }

    /**
     * @param ${ENTRY_HINT} $productSetProductActivePrice
     *
     * @return CartItemSummary
     */
    public function removeProductSetProductActivePrice($productSetProductActivePrice): CartItemSummary
    {
        if (true === in_array($productSetProductActivePrice, $this->productSetProductActivePrices, true)) {
            $index = array_search($productSetProductActivePrice, $this->productSetProductActivePrices);
            array_splice($this->productSetProductActivePrices, $index, 1);
        }
        return $this;
    }

    /**
     * @param array $productSetProductActivePrices
     * @return CartItemSummary
     */
    public function setProductSetProductActivePrices(array $productSetProductActivePrices): CartItemSummary
    {
        $this->productSetProductActivePrices = $productSetProductActivePrices;
        return $this;
    }
}
