<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use LSB\UtilityBundle\Calculation\CalculationTypeTrait;
use LSB\UtilityBundle\Helper\ValueHelper;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Trait ValueCostTrait
 * @package LSB\OrderBundle\Entity
 */
trait ValueCostTrait
{
    use CalculationTypeTrait;

    /**
     * @var string|null
     * @ORM\Column(type="decimal", precision=18, scale=2, nullable=true, options={"default": 0})
     * @Assert\Type(type="numeric")
     */
    protected ?string $totalValueNet = "0";

    /**
     * @var string|null
     * @ORM\Column(type="decimal", precision=18, scale=2, nullable=true, options={"default": 0})
     */
    protected ?string $totalValueGross = "0";

    /**
     * @var string|null
     * @ORM\Column(type="decimal", precision=18, scale=2, nullable=true, options={"default": 0})
     */
    protected ?string $shippingCostNet = "0";

    /**
     * @var string|null
     * @ORM\Column(type="decimal", precision=18, scale=2, options={"default": 0})
     */
    protected ?string $shippingCostGross = "0";

    /**
     * @var integer|null
     * @ORM\Column(type="integer", nullable=true)
     */
    protected ?int $shippingCostTaxRate;

    /**
     * @var integer|null
     * @ORM\Column(type="integer", nullable=true)
     */
    protected ?int $paymentCostTaxRate;

    /**
     * @var string|null
     * @ORM\Column(type="decimal", precision=18, scale=2, nullable=true, options={"default": 0})
     */
    protected ?string $paymentCostNet = "0";

    /**
     * @var string|null
     * @ORM\Column(type="decimal", precision=18, scale=2, nullable=true, options={"default": 0})
     */
    protected ?string $paymentCostGross = "0";

    /**
     * @var string|null
     * @ORM\Column(type="decimal", precision=18, scale=2, nullable=true, options={"default": 0})
     */
    protected ?string $productsValueNet = "0";

    /**
     * @var string|null
     * @ORM\Column(type="decimal", precision=18, scale=2, nullable=true, options={"default": 0})
     */
    protected ?string $productsValueGross = "0";

    /**
     * @return float|null
     */
    public function getTotalValueNet(): ?float
    {
        return ValueHelper::toFloat($this->totalValueNet);
    }

    /**
     * @param float|string|null $totalValueNet
     * @return $this
     */
    public function setTotalValueNet(float|string|null $totalValueNet): static
    {
        $this->totalValueNet = ValueHelper::toString($totalValueNet);
        return $this;
    }

    /**
     * @return float|null
     */
    public function getTotalValueGross(): ?float
    {
        return ValueHelper::toFloat($this->totalValueGross);
    }

    /**
     * @param float|string|null $totalValueGross
     * @return $this
     */
    public function setTotalValueGross(float|string|null $totalValueGross): static
    {
        $this->totalValueGross = ValueHelper::toString($totalValueGross);
        return $this;
    }

    /**
     * @return float|null
     */
    public function getShippingCostNet(): ?float
    {
        return ValueHelper::toFloat($this->shippingCostNet);
    }

    /**
     * @param float|string|null $shippingCostNet
     * @return $this
     */
    public function setShippingCostNet(float|string|null $shippingCostNet): static
    {
        $this->shippingCostNet = ValueHelper::toString($shippingCostNet);
        return $this;
    }

    /**
     * @return float|null
     */
    public function getShippingCostGross(): ?float
    {
        return ValueHelper::toFloat($this->shippingCostGross);
    }

    /**
     * @param float|string|null $shippingCostGross
     * @return $this
     */
    public function setShippingCostGross(float|string|null $shippingCostGross): static
    {
        $this->shippingCostGross = ValueHelper::toString($shippingCostGross);
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
     * @return $this
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
     * @return $this
     */
    public function setPaymentCostTaxRate(?int $paymentCostTaxRate): static
    {
        $this->paymentCostTaxRate = $paymentCostTaxRate;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getPaymentCostNet(): ?float
    {
        return ValueHelper::toFloat($this->paymentCostNet);
    }

    /**
     * @param float|string|null $paymentCostNet
     * @return $this
     */
    public function setPaymentCostNet(float|string|null $paymentCostNet): static
    {
        $this->paymentCostNet = ValueHelper::toString($paymentCostNet);
        return $this;
    }

    /**
     * @return float|null
     */
    public function getPaymentCostGross(): ?float
    {
        return ValueHelper::toFloat($this->paymentCostGross);
    }

    /**
     * @param float|string|null $paymentCostGross
     * @return $this
     */
    public function setPaymentCostGross(float|string|null $paymentCostGross): static
    {
        $this->paymentCostGross = ValueHelper::toString($paymentCostGross);
        return $this;
    }

    /**
     * @return float|null
     */
    public function getProductsValueNet(): ?float
    {
        return ValueHelper::toFloat($this->productsValueNet);
    }

    /**
     * @param float|string|null $productsValueNet
     * @return $this
     */
    public function setProductsValueNet(float|string|null $productsValueNet): static
    {
        $this->productsValueNet = ValueHelper::toString($productsValueNet);
        return $this;
    }

    /**
     * @return float|null
     */
    public function getProductsValueGross(): ?float
    {
        return ValueHelper::toFloat($this->productsValueGross);
    }

    /**
     * @param float|string|null $productsValueGross
     * @return $this
     */
    public function setProductsValueGross(float|string|null $productsValueGross): static
    {
        $this->productsValueGross = ValueHelper::toString($productsValueGross);
        return $this;
    }


}