<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use LSB\LocaleBundle\Entity\CurrencyInterface;
use LSB\UtilityBundle\Helper\ValueHelper;
use LSB\UtilityBundle\Value\Value;
use Money\Currency;
use Money\Money;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Trait ItemValueTrait
 * @package LSB\OrderBundle\Entity
 */
trait ItemValueTrait
{

    /**
     * @var int|null
     * @ORM\Column(type="integer", nullable=true)
     */
    protected ?int $quantity = null;

    /**
     * @var CurrencyInterface|null
     * @ORM\ManyToOne(targetEntity="LSB\LocaleBundle\Entity\CurrencyInterface")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected ?CurrencyInterface $currency = null;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=5, options={"default": "PLN"})
     */
    protected ?string $currencyIsoCode = null;

    /**
     * @var int|null
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\Type(type="numeric")
     */
    protected ?int $priceNet = null;

    /**
     * @var int|null
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\Type(type="numeric")
     */
    protected ?int $priceGross = null;

    /**
     * @var int|null
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\Type(type="numeric")
     */
    protected ?int $valueNet = null;

    /**
     * @var int|null
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\Type(type="numeric")
     */
    protected ?int $valueGross = null;

    /**
     * @var int|null
     * @ORM\Column(type="integer", nullable=true)
     */
    protected ?int $taxRate;

    /**
     * @var int|null
     * @ORM\Column(type="integer", nullable=true)
     */
    protected ?int $taxValue;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    protected ?string $unit = null;

    /**
     * @var int|null
     * @ORM\Column(type="integer", nullable=true)
     */
    protected ?int $productSetQuantity = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    protected ?string $productSetUnit = null;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     * @Assert\Length(max="50")
     */
    protected ?string $orderCode = null;

    /**
     * @return CurrencyInterface|null
     */
    public function getCurrency(): ?CurrencyInterface
    {
        return $this->currency;
    }

    /**
     * @param CurrencyInterface|null $currency
     * @return $this
     */
    public function setCurrency(?CurrencyInterface $currency): static
    {
        $this->currency = $currency;
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
     * @return $this
     */
    public function setCurrencyIsoCode(?string $currencyIsoCode): static
    {
        $this->currencyIsoCode = $currencyIsoCode;
        return $this;
    }

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
     * @return $this
     */
    public function setPriceNet(Money|int|null $priceNet): static
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
     * @return $this
     */
    public function setPriceGross(Money|int|null $priceGross): static
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
     * @param Money|int|null $valueNet
     * @return $this
     */
    public function setValueNet(Money|int|null $valueNet): static
    {
        if ($valueNet instanceof Money) {
            [$amount, $currency] = ValueHelper::moneyToIntCurrency($valueNet);
            $this->valueNet = $amount;
            $this->currencyIsoCode = $currency;
            return $this;
        }

        $this->valueNet = $valueNet;
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
     * @return $this
     */
    public function setValueGross(Money|int|null $valueGross): static
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
     * @param bool $useValue
     * @return Value|int|null
     */
    public function getTaxRate(bool $useValue = false): Value|int|null
    {
        return $useValue ? ValueHelper::intToValue($this->taxRate, Value::UNIT_PERCENTAGE) : $this->taxRate;
    }

    /**
     * @param Value|int|null $taxRate
     * @return $this
     */
    public function setTaxRate(Value|int|null $taxRate): static
    {
        if ($taxRate instanceof Value)
        {
            [$amount, $unit] = ValueHelper::valueToIntUnit($taxRate);
            $this->taxRate = $amount;
            return $this;
        }


        $this->taxRate = $taxRate;
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
     * @return $this
     */
    public function setTaxValue(Money|int|null $taxValue): static
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
    public function getQuantity(bool $useValue = false): Value|int|null
    {
        return $useValue ? ValueHelper::intToValue($this->quantity, $this->unit) : $this->quantity;
    }

    /**
     * @param Value|int|null $quantity
     * @return $this
     */
    public function setQuantity(Value|int|null $quantity): static
    {
        if ($quantity instanceof Value)
        {
            [$amount, $unit] = ValueHelper::valueToIntUnit($quantity);
            $this->quantity = $amount;
            $this->unit = $unit;
            return $this;
        }

        $this->quantity = $quantity;
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
     * @return $this
     */
    public function setUnit(?string $unit): static
    {
        $this->unit = $unit;
        return $this;
    }

    /**
     * @param bool $useValue
     * @return Value|int|null
     */
    public function getProductSetQuantity(bool $useValue = false): Value|int|null
    {
        return $useValue ? ValueHelper::intToValue($this->productSetQuantity, $this->productSetUnit) : $this->productSetQuantity;
    }

    /**
     * @param Value|int|null $productSetQuantity
     * @return $this
     */
    public function setProductSetQuantity(Value|int|null $productSetQuantity): static
    {
        if ($productSetQuantity instanceof Value)
        {
            [$amount, $unit] = ValueHelper::valueToIntUnit($productSetQuantity);
            $this->productSetQuantity = $amount;
            $this->productSetUnit = $unit;
            return $this;
        }

        $this->productSetQuantity = $productSetQuantity;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getProductSetUnit(): ?string
    {
        return $this->productSetUnit;
    }

    /**
     * @param string|null $productSetUnit
     * @return $this
     */
    public function setProductSetUnit(?string $productSetUnit): static
    {
        $this->productSetUnit = $productSetUnit;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getOrderCode(): ?string
    {
        return $this->orderCode;
    }

    /**
     * @param string|null $orderCode
     * @return $this
     */
    public function setOrderCode(?string $orderCode): static
    {
        $this->orderCode = $orderCode;
        return $this;
    }
}