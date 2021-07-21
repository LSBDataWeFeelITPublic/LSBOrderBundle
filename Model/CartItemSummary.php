<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Model;

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
     * @Groups({"Default", "EDI_Price", "SHOP_Public"})
     *
     * @var float|null
     */
    protected $priceNetto = null;

    /**
     * @Groups({"Default", "EDI_Price", "SHOP_Public"})
     *
     * @var float|null
     */
    protected $priceGross = null;

    /**
     * @Groups({"Default", "EDI_Price", "SHOP_Public"})
     *
     * @var float|null
     */
    protected $valueNetto = null;

    /**
     * @Groups({"Default", "EDI_Price", "SHOP_Public"})
     *
     * @var float|null
     */
    protected $valueGross = null;

    /**
     * @var float|null
     */
    protected $basePriceNetto = null;

    /**
     * @var float|null
     */
    protected $basePriceGross = null;

    /**
     * @var float|null
     */
    protected $baseValueNetto = null;

    /**
     * @var float|null
     */
    protected $baseValueGross = null;

    /**
     * @Groups({"Default", "EDI_Price", "SHOP_Public"})
     *
     * @var float|null
     */
    protected $taxValue = null;

    /**
     * @Groups({"Default", "EDI_Price", "SHOP_Public"})
     *
     * @var integer|null
     */
    protected $tax = null;

    /**
     * @var array
     */
    protected $res = [];

    /**
     * @Groups({"Default", "EDI_User", "SHOP_Public"})
     *
     * @var null|float
     */
    protected $quantity;

    /**
     * @var \DateTime|null
     */
    protected $calculatedAt;

    /**
     * @var Price|null
     */
    protected $activePrice;

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
     * @var bool
     */
    protected $isProductSet = false;

    /**
     * W przypadku zestawów należy przechowywać pulę obiektów;
     *
     * @var array
     */
    protected $productSetProductActivePrices = [];

    /*
     * ----------------
     * Metody dodatkowe
     * ----------------
     */

    /**
     * Metoda weryfikuję potrzebę wyświetlenia ceny bazowej netto
     * @return bool
     */
    public function isShowBaseNettoPrice(): bool
    {
        if ($this->basePriceNetto && $this->priceNetto && $this->basePriceNetto !== $this->priceNetto) {
            return true;
        }

        return false;
    }

    /**
     * Metoda weryfikuję potrzebę wyświetlenia ceny bazowej brutto
     * @return bool
     */
    public function isShowBaseGrossPrice(): bool
    {
        if ($this->basePriceGross && $this->priceGross && $this->basePriceGross !== $this->priceGross) {
            return true;
        }

        return false;
    }

    /*
     * -----------------
     * Gettery i settery
     * -----------------
     */


    /**
     * @return float|null
     */
    public function getPriceNetto(): ?float
    {
        return $this->priceNetto;
    }

    /**
     * @param float $priceNetto
     * @return CartItemSummary
     */
    public function setPriceNetto(float $priceNetto): CartItemSummary
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
     * @param float $priceGross
     * @return CartItemSummary
     */
    public function setPriceGross(float $priceGross): CartItemSummary
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
     * @param float $valueNetto
     * @return CartItemSummary
     */
    public function setValueNetto(float $valueNetto): CartItemSummary
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
     * @param float $valueGross
     * @return CartItemSummary
     */
    public function setValueGross(float $valueGross): CartItemSummary
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
     * @param float $basePriceNetto
     * @return CartItemSummary
     */
    public function setBasePriceNetto(float $basePriceNetto): CartItemSummary
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
     * @param float $basePriceGross
     * @return CartItemSummary
     */
    public function setBasePriceGross(float $basePriceGross): CartItemSummary
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
     * @param float $baseValueNetto
     * @return CartItemSummary
     */
    public function setBaseValueNetto(float $baseValueNetto): CartItemSummary
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
     * @param float $baseValueGross
     * @return CartItemSummary
     */
    public function setBaseValueGross(float $baseValueGross): CartItemSummary
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
     * @param float $taxValue
     * @return CartItemSummary
     */
    public function setTaxValue(float $taxValue): CartItemSummary
    {
        $this->taxValue = $taxValue;

        return $this;
    }

    /**
     * @return int
     */
    public function getTax(): ?int
    {
        return $this->tax;
    }

    /**
     * @param int|null $tax
     * @return CartItemSummary
     */
    public function setTax(?int $tax): CartItemSummary
    {
        $this->tax = $tax;

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
     * @return \DateTime|null
     */
    public function getCalculatedAt(): ?\DateTime
    {
        return $this->calculatedAt;
    }

    /**
     * @param \DateTime|null $calculatedAt
     * @return CartItemSummary
     */
    public function setCalculatedAt(?\DateTime $calculatedAt): CartItemSummary
    {
        $this->calculatedAt = $calculatedAt;
        return $this;
    }

    /**
     * @return Price|null
     */
    public function getActivePrice(): ?Price
    {
        return $this->activePrice;
    }

    /**
     * @param Price|null $activePrice
     * @return CartItemSummary
     */
    public function setActivePrice(?Price $activePrice): CartItemSummary
    {
        $this->activePrice = $activePrice;
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
     * @return CartItemSummary
     */
    public function setCurrencyCode(?string $currencyCode): CartItemSummary
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
     * @param array $productSetProductActivePrices
     * @return CartItemSummary
     */
    public function setProductSetProductActivePrices(array $productSetProductActivePrices): CartItemSummary
    {
        $this->productSetProductActivePrices = $productSetProductActivePrices;
        return $this;
    }

    /**
     * @param int $productId
     * @return bool
     */
    public function hasProductSetProductActivePriceByProductId(int $productId): bool
    {
        if (array_key_exists($productId, $this->productSetProductActivePrices) && $this->productSetProductActivePrices[$productId] instanceof Price) {
            return true;
        }

        return false;
    }

    /**
     * @param int $productId
     * @return Price
     * @throws \Exception
     */
    public function getProductSetProductActivePriceByProductId(int $productId): Price
    {
        if (array_key_exists($productId, $this->productSetProductActivePrices) && $this->productSetProductActivePrices[$productId] instanceof Price) {
            return $this->productSetProductActivePrices[$productId];
        }

        throw new \Exception("Active price for product:{$productId} not exists.");
    }
}
