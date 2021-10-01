<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Model;

use DateTime;
use LSB\PricelistBundle\Model\Price;
use LSB\UtilityBundle\Helper\ValueHelper;
use LSB\UtilityBundle\Value\Value;
use Money\Money;

/**
 * Class CartItemSummary
 * @package LSB\CartBundle\Model
 */
class CartItemSummary
{
    protected ?int $priceNet = null;

    protected ?int $priceGross = null;

    protected ?int $valueNet = null;

    protected ?int $valueGross = null;

    protected ?int $basePriceNet = null;

    protected ?int $basePriceGross = null;

    protected ?int $baseValueNet = null;

    protected ?int $baseValueGross = null;

    protected ?int $taxValue = null;

    protected ?int $taxRate = null;

    protected ?int $quantity = null;

    protected ?string $unit = null;

    /**
     * @var array
     */
    protected array $res = [];

    /**
     * @var DateTime|null
     */
    protected ?DateTime $calculatedAt = null;

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
     * @var Price|null
     */
    protected ?Price $activePrice = null;

    /**
     * @param bool $useMoney
     * @return Money|int|null
     */
    public function getPriceNet(bool $useMoney = false): Money|int|null
    {
        return $useMoney ? ValueHelper::intToMoney($this->priceNet, $this->currencyIsoCode) : $this->priceNet;
    }

    /**
     * @param Money|int|null $priceNet
     * @return CartItemSummary
     */
    public function setPriceNet(Money|int|null $priceNet): CartItemSummary
    {
        if ($priceNet instanceof Money) {
            [$amount, $currency] = ValueHelper::moneyToIntCurrency($priceNet);
            $this->priceNet = $amount;
            $this->currencyIsoCode = $currency;
            return $this;
        }

        $this->priceNet = $priceNet;
        return $this;
    }

    /**
     * @param bool $useMoney
     * @return Money|int|null
     */
    public function getPriceGross(bool $useMoney = false): Money|int|null
    {
        return $useMoney ? ValueHelper::intToMoney($this->priceGross, $this->currencyIsoCode) : $this->priceGross;
    }

    /**
     * @param Money|int|null $priceGross
     * @return CartItemSummary
     */
    public function setPriceGross(Money|int|null $priceGross): CartItemSummary
    {
        if ($priceGross instanceof Money) {
            [$amount, $currency] = ValueHelper::moneyToIntCurrency($priceGross);
            $this->priceGross = $amount;
            $this->currencyIsoCode = $currency;
            return $this;
        }

        $this->priceGross = $priceGross;
        return $this;
    }

    /**
     * @param bool $useMoney
     * @return Money|int|null
     */
    public function getValueNet(bool $useMoney = false): Money|int|null
    {
        return $useMoney ? ValueHelper::intToMoney($this->valueNet, $this->currencyIsoCode) : $this->valueNet;
    }

    /**
     * @param Money|int|null $valueNetto
     * @return CartItemSummary
     */
    public function setValueNet(Money|int|null $valueNetto): CartItemSummary
    {
        if ($valueNetto instanceof Money) {
            [$amount, $currency] = ValueHelper::moneyToIntCurrency($valueNetto);
            $this->valueNet = $amount;
            $this->currencyIsoCode = $currency;
            return $this;
        }

        $this->valueNet = $valueNetto;
        return $this;
    }

    /**
     * @param bool $useMoney
     * @return Money|int|null
     */
    public function getValueGross(bool $useMoney = false): Money|int|null
    {
        return $useMoney ? ValueHelper::intToMoney($this->valueGross, $this->currencyIsoCode) : $this->valueGross;
    }

    /**
     * @param Money|int|null $valueGross
     * @return CartItemSummary
     */
    public function setValueGross(Money|int|null $valueGross): CartItemSummary
    {
        if ($valueGross instanceof Money) {
            [$amount, $currency] = ValueHelper::moneyToIntCurrency($valueGross);
            $this->valueGross = $amount;
            $this->currencyIsoCode = $currency;
            return $this;
        }

        $this->valueGross = $valueGross;
        return $this;
    }

    /**
     * @param bool $useMoney
     * @return Money|int|null
     */
    public function getBasePriceNet(bool $useMoney = false): Money|int|null
    {
        return $useMoney ? ValueHelper::intToMoney($this->basePriceNet, $this->currencyIsoCode) : $this->basePriceNet;
    }

    /**
     * @param Money|int|null $basePriceNet
     * @return CartItemSummary
     */
    public function setBasePriceNet(Money|int|null $basePriceNet): CartItemSummary
    {
        if ($basePriceNet instanceof Money) {
            [$amount, $currency] = ValueHelper::moneyToIntCurrency($basePriceNet);
            $this->basePriceNet = $amount;
            $this->currencyIsoCode = $currency;
            return $this;
        }

        $this->basePriceNet = $basePriceNet;
        return $this;
    }

    /**
     * @param bool $useMoney
     * @return Money|int|null
     */
    public function getBasePriceGross(bool $useMoney = false): Money|int|null
    {
        return $useMoney ? ValueHelper::intToMoney($this->basePriceGross, $this->currencyIsoCode) : $this->basePriceGross;
    }

    /**
     * @param Money|int|null $basePriceGross
     * @return CartItemSummary
     */
    public function setBasePriceGross(Money|int|null $basePriceGross): CartItemSummary
    {
        if ($basePriceGross instanceof Money) {
            [$amount, $currency] = ValueHelper::moneyToIntCurrency($basePriceGross);
            $this->basePriceGross = $amount;
            $this->currencyIsoCode = $currency;
            return $this;
        }

        $this->basePriceGross = $basePriceGross;
        return $this;
    }

    /**
     * @param bool $useMoney
     * @return Money|int|null
     */
    public function getBaseValueNet(bool $useMoney = false): Money|int|null
    {
        return $useMoney ? ValueHelper::intToMoney($this->baseValueNet, $this->currencyIsoCode) : $this->baseValueNet;
    }

    /**
     * @param Money|int|null $baseValueNet
     * @return CartItemSummary
     */
    public function setBaseValueNet(Money|int|null $baseValueNet): CartItemSummary
    {
        if ($baseValueNet instanceof Money) {
            [$amount, $currency] = ValueHelper::moneyToIntCurrency($baseValueNet);
            $this->baseValueNet = $amount;
            $this->currencyIsoCode = $currency;
            return $this;
        }

        $this->baseValueNet = $baseValueNet;
        return $this;
    }

    /**
     * @param bool $useMoney
     * @return Money|int|null
     */
    public function getBaseValueGross(bool $useMoney = false): Money|int|null
    {
        return $useMoney ? ValueHelper::intToMoney($this->baseValueGross, $this->currencyIsoCode) : $this->baseValueGross;
    }

    /**
     * @param Money|int|null $baseValueGross
     * @return CartItemSummary
     */
    public function setBaseValueGross(Money|int|null $baseValueGross): CartItemSummary
    {
        if ($baseValueGross instanceof Money) {
            [$amount, $currency] = ValueHelper::moneyToIntCurrency($baseValueGross);
            $this->baseValueGross = $amount;
            $this->currencyIsoCode = $currency;
            return $this;
        }

        $this->baseValueGross = $baseValueGross;
        return $this;
    }

    /**
     * @param bool $useMoney
     * @return Money|int|null
     */
    public function getTaxValue(bool $useMoney = false): Money|int|null
    {
        return $useMoney ? ValueHelper::intToMoney($this->taxValue, $this->currencyIsoCode) : $this->taxValue;
    }

    /**
     * @param Money|int|null $taxValue
     * @return CartItemSummary
     */
    public function setTaxValue(Money|int|null $taxValue): CartItemSummary
    {
        if ($taxValue instanceof Money) {
            [$amount, $currency] = ValueHelper::moneyToIntCurrency($taxValue);
            $this->taxValue = $amount;
            $this->currencyIsoCode = $currency;
            return $this;
        }

        $this->taxValue = $taxValue;
        return $this;
    }

    /**
     * @param bool $useValue
     * @return Value|int|null
     */
    public function getTaxRate(bool $useValue = false): Value|int|null
    {
        return $useValue ? ValueHelper::intToValue($this->taxRate, $this->currencyIsoCode) : $this->taxRate;
    }

    /**
     * @param Value|int|null $taxRate
     * @return CartItemSummary
     */
    public function setTaxRate(Value|int|null $taxRate): CartItemSummary
    {
        if ($taxRate instanceof Value) {
            [$amount, $currency] = ValueHelper::valueToIntUnit($taxRate);
            $this->taxRate = $amount;
            $this->currencyIsoCode = $currency;
            return $this;
        }

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
     * @param bool $useValue
     * @return Value|int|null
     */
    public function getQuantity(bool $useValue = false): Value|int|null
    {
        return $useValue ? ValueHelper::intToValue($this->quantity, $this->unit) : $this->quantity;
    }

    /**
     * @param Value|int|null $quantity
     * @return CartItemSummary
     */
    public function setQuantity(Value|int|null $quantity): CartItemSummary
    {
        if ($quantity instanceof Value) {
            [$amount, $unit] = ValueHelper::valueToIntUnit($quantity);
            $this->quantity = $amount;
            $this->unit = $unit;
            return $this;
        }

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
     * @param $productSetProductActivePrice
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
     * @param $productSetProductActivePrice
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
    public function getUnit(): ?string
    {
        return $this->unit;
    }

    /**
     * @param string|null $unit
     * @return CartItemSummary
     */
    public function setUnit(?string $unit): CartItemSummary
    {
        $this->unit = $unit;
        return $this;
    }
}
