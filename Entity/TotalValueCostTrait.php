<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use LSB\LocaleBundle\Entity\CurrencyInterface;
use LSB\UtilityBundle\Calculation\CalculationTypeTrait;
use LSB\UtilityBundle\Helper\ValueHelper;
use Money\Currency;
use Money\Money;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Trait ValueCostTrait
 * @package LSB\OrderBundle\Entity
 */
trait TotalValueCostTrait
{
    use CalculationTypeTrait;

    /**
     * @var int|null
     * @ORM\Column(type="integer", nullable=true)
     */
    protected ?int $totalValueNet = 0;

    /**
     * @var int|null
     * @ORM\Column(type="integer", nullable=true)
     */
    protected ?int $totalValueGross = 0;

    /**
     * @var int|null
     * @ORM\Column(type="integer", nullable=true)
     */
    protected ?int $shippingCostNet = 0;

    /**
     * @var int|null
     * @ORM\Column(type="integer", nullable=true)
     */
    protected ?int $shippingCostGross = 0;

    /**
     * @var integer|null
     * @ORM\Column(type="integer", nullable=true)
     */
    protected ?int $shippingCostTaxRate;

    /**
     * @var int|null
     * @ORM\Column(type="integer", nullable=true)
     */
    protected ?int $paymentCostTaxRate;

    /**
     * @var int|null
     * @ORM\Column(type="integer", nullable=true)
     */
    protected ?int $paymentCostNet = 0;

    /**
     * @var int|null
     * @ORM\Column(type="integer", nullable=true)
     */
    protected ?int $paymentCostGross = 0;

    /**
     * @var int|null
     * @ORM\Column(type="integer", nullable=true)
     */
    protected ?int $productsValueNet = 0;

    /**
     * @var int|null
     * @ORM\Column(type="integer", nullable=true)
     */
    protected ?int $productsValueGross = 0;

    /**
     * @var CurrencyInterface|null
     * @ORM\ManyToOne(targetEntity="LSB\LocaleBundle\Entity\CurrencyInterface")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected ?CurrencyInterface $currency = null;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=5, options={"default": "PLN"})
     */
    protected string $currencyIsoCode = 'PLN';

    /**
     * @param bool $useMoney
     * @return Money|int|null
     */
    public function getTotalValueNet(bool $useMoney): Money|int|null
    {
        return $useMoney ? ValueHelper::intToMoney($this->totalValueNet, $this->currencyIsoCode) : $this->totalValueNet;
    }

    /**
     * @param Money|int|null $totalValueNet
     * @return TotalValueCostTrait
     */
    public function setTotalValueNet(Money|int|null $totalValueNet): static
    {
        if ($totalValueNet instanceof Money) {
            [$amount, $currency] = ValueHelper::moneyToIntCurrency($totalValueNet);
            $this->totalValueNet = $amount;
            $this->currencyIsoCode = $currency;
            return $this;
        }

        $this->totalValueNet = $totalValueNet;
        return $this;
    }

    /**
     * @param bool $useMoney
     * @return Money|int|null
     */
    public function getTotalValueGross(bool $useMoney = false): Money|int|null
    {
        return $useMoney ? ValueHelper::intToMoney($this->totalValueGross, $this->currencyIsoCode) : $this->totalValueGross;
    }

    /**
     * @param Money|int|null $totalValueGross
     * @return TotalValueCostTrait
     */
    public function setTotalValueGross(Money|int|null $totalValueGross): static
    {
        if ($totalValueGross instanceof Money) {
            [$amount, $currency] = ValueHelper::moneyToIntCurrency($totalValueGross);
            $this->totalValueGross = $amount;
            $this->currencyIsoCode = $currency;
            return $this;
        }

        $this->totalValueGross = $totalValueGross;
        return $this;
    }

    /**
     * @param bool $useMoney
     * @return Money|int|null
     */
    public function getShippingCostNet(bool $useMoney = false): Money|int|null
    {
        return $useMoney ? ValueHelper::intToMoney($this->shippingCostNet, $this->currencyIsoCode) : $this->shippingCostNet;
    }

    /**
     * @param Money|int|null $shippingCostNet
     * @return TotalValueCostTrait
     */
    public function setShippingCostNet(Money|int|null $shippingCostNet): static
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
     * @param bool $useMoney
     * @return Money|int|null
     */
    public function getShippingCostGross(bool $useMoney = false): Money|int|null
    {
        return $useMoney ? ValueHelper::intToMoney($this->shippingCostGross, $this->currencyIsoCode) : $this->shippingCostGross;
    }

    /**
     * @param Money|int|null $shippingCostGross
     * @return TotalValueCostTrait
     */
    public function setShippingCostGross(Money|int|null $shippingCostGross): static
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
     * @return int|null
     */
    public function getShippingCostTaxRate(): ?int
    {
        return $this->shippingCostTaxRate;
    }

    /**
     * @param int|null $shippingCostTaxRate
     * @return TotalValueCostTrait
     */
    public function setShippingCostTaxRate(?int $shippingCostTaxRate): static
    {
        $this->shippingCostTaxRate = $shippingCostTaxRate;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getPaymentCostTaxRate(): ?int
    {
        return $this->paymentCostTaxRate;
    }

    /**
     * @param int|null $paymentCostTaxRate
     * @return TotalValueCostTrait
     */
    public function setPaymentCostTaxRate(?int $paymentCostTaxRate): static
    {
        $this->paymentCostTaxRate = $paymentCostTaxRate;
        return $this;
    }

    /**
     * @param bool $useMoney
     * @return Money|int|null
     */
    public function getPaymentCostNet(bool $useMoney = false): Money|int|null
    {
        return $useMoney ? ValueHelper::intToMoney($this->paymentCostNet, $this->currencyIsoCode) : $this->paymentCostNet;
    }

    /**
     * @param Money|int|null $paymentCostNet
     * @return $this
     */
    public function setPaymentCostNet(Money|int|null $paymentCostNet): static
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
     * @param bool $useMoney
     * @return Money|int|null
     */
    public function getPaymentCostGross(bool $useMoney = false): Money|int|null
    {
        return $useMoney ? ValueHelper::intToMoney($this->paymentCostGross, $this->currencyIsoCode) : $this->paymentCostGross;
    }

    /**
     * @param Money|int|null $paymentCostGross
     * @return TotalValueCostTrait
     */
    public function setPaymentCostGross(Money|int|null $paymentCostGross): static
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
     * @param bool $useMoney
     * @return Money|int|null
     */
    public function getProductsValueNet(bool $useMoney = false): Money|int|null
    {
        return $useMoney ? ValueHelper::intToMoney($this->productsValueNet, $this->currencyIsoCode) : $this->productsValueNet;
    }

    /**
     * @param int|null $productsValueNet
     * @return TotalValueCostTrait
     */
    public function setProductsValueNet(Money|int|null $productsValueNet): static
    {
        if ($productsValueNet instanceof Money) {
            [$amount, $currency] = ValueHelper::moneyToIntCurrency($productsValueNet);
            $this->productsValueNet = $amount;
            $this->currencyIsoCode = $currency;
            return $this;
        }

        $this->productsValueNet = $productsValueNet;
        return $this;
    }

    /**
     * @param bool $useMoney
     * @return Money|int|null
     */
    public function getProductsValueGross(bool $useMoney = false): Money|int|null
    {
        return $useMoney ? ValueHelper::intToMoney($this->productsValueGross, $this->currencyIsoCode) : $this->productsValueGross;
    }

    /**
     * @param Money|int|null $productsValueGross
     * @return TotalValueCostTrait
     */
    public function setProductsValueGross(Money|int|null $productsValueGross): static
    {
        if ($productsValueGross instanceof Money) {
            [$amount, $currency] = ValueHelper::moneyToIntCurrency($productsValueGross);
            $this->productsValueGross = $amount;
            $this->currencyIsoCode = $currency;
            return $this;
        }

        $this->productsValueGross = $productsValueGross;
        return $this;
    }

    /**
     * @return CurrencyInterface|null
     */
    public function getCurrency(): ?CurrencyInterface
    {
        return $this->currency;
    }

    /**
     * @param CurrencyInterface|null $currency
     * @return TotalValueCostTrait
     */
    public function setCurrency(?CurrencyInterface $currency): static
    {
        $this->currency = $currency;
        return $this;
    }

    /**
     * @return string
     */
    public function getCurrencyIsoCode(): string
    {
        return $this->currencyIsoCode;
    }

    /**
     * @param string $currencyIsoCode
     * @return TotalValueCostTrait
     */
    public function setCurrencyIsoCode(string $currencyIsoCode): static
    {
        $this->currencyIsoCode = $currencyIsoCode;
        return $this;
    }
}