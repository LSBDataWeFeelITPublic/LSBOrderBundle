<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Trait ValueCostTrait
 * @package LSB\OrderBundle\Entity
 */
trait ValueCostTrait
{
    /**
     * @var float|string
     * @ORM\Column(type="decimal", precision=18, scale=2, nullable=true, options={"default": 0})
     * @Assert\Type(type="numeric")
     */
    protected $totalValueNet = 0;

    /**
     * @var float|string
     * @ORM\Column(type="decimal", precision=18, scale=2, nullable=true, options={"default": 0})
     */
    protected $totalValueGross = 0;

    /**
     * @var float|string|null
     * @ORM\Column(type="decimal", precision=18, scale=2, nullable=true, options={"default": 0})
     */
    protected $shippingCostNet = 0;

    /**
     * @var float|string|null
     * @ORM\Column(type="decimal", precision=18, scale=2, options={"default": 0})
     */
    protected $shippingCostGross = 0;

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
     * @var float|string|null
     * @ORM\Column(type="decimal", precision=18, scale=2, nullable=true, options={"default": 0})
     */
    protected $paymentCostNet = 0;

    /**
     * @var float|string|null
     * @ORM\Column(type="decimal", precision=18, scale=2, nullable=true, options={"default": 0})
     */
    protected $paymentCostGross = 0;

    /**
     * @var float|string|null
     * @ORM\Column(type="decimal", precision=18, scale=2, nullable=true, options={"default": 0})
     */
    protected $productsValueNet = 0;

    /**
     * @var float|string|null
     * @ORM\Column(type="decimal", precision=18, scale=2, nullable=true, options={"default": 0})
     */
    protected $productsValueGross = 0;

    /**
     * @return float|string
     */
    public function getTotalValueNet()
    {
        return $this->totalValueNet;
    }

    /**
     * @param float|string $totalValueNet
     * @return $this
     */
    public function setTotalValueNet($totalValueNet): self
    {
        $this->totalValueNet = $totalValueNet;
        return $this;
    }

    /**
     * @return float|string
     */
    public function getTotalValueGross()
    {
        return $this->totalValueGross;
    }

    /**
     * @param float|string $totalValueGross
     * @return $this
     */
    public function setTotalValueGross($totalValueGross): self
    {
        $this->totalValueGross = $totalValueGross;
        return $this;
    }

    /**
     * @return float|string|null
     */
    public function getShippingCostNet()
    {
        return $this->shippingCostNet;
    }

    /**
     * @param float|string|null $shippingCostNet
     * @return $this
     */
    public function setShippingCostNet($shippingCostNet): self
    {
        $this->shippingCostNet = $shippingCostNet;
        return $this;
    }

    /**
     * @return float|string|null
     */
    public function getShippingCostGross()
    {
        return $this->shippingCostGross;
    }

    /**
     * @param float|string|null $shippingCostGross
     * @return $this
     */
    public function setShippingCostGross($shippingCostGross): self
    {
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
     * @return $this
     */
    public function setShippingCostTaxRate(?int $shippingCostTaxRate): self
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
    public function setPaymentCostTaxRate(?int $paymentCostTaxRate): self
    {
        $this->paymentCostTaxRate = $paymentCostTaxRate;
        return $this;
    }

    /**
     * @return float|string|null
     */
    public function getPaymentCostNet()
    {
        return $this->paymentCostNet;
    }

    /**
     * @param float|string|null $paymentCostNet
     * @return $this
     */
    public function setPaymentCostNet($paymentCostNet): self
    {
        $this->paymentCostNet = $paymentCostNet;
        return $this;
    }

    /**
     * @return float|string|null
     */
    public function getPaymentCostGross()
    {
        return $this->paymentCostGross;
    }

    /**
     * @param float|string|null $paymentCostGross
     * @return $this
     */
    public function setPaymentCostGross($paymentCostGross): self
    {
        $this->paymentCostGross = $paymentCostGross;
        return $this;
    }

    /**
     * @return float|string|null
     */
    public function getProductsValueNet()
    {
        return $this->productsValueNet;
    }

    /**
     * @param float|string|null $productsValueNet
     * @return $this
     */
    public function setProductsValueNet($productsValueNet): self
    {
        $this->productsValueNet = $productsValueNet;
        return $this;
    }

    /**
     * @return float|string|null
     */
    public function getProductsValueGross()
    {
        return $this->productsValueGross;
    }

    /**
     * @param float|string|null $productsValueGross
     * @return $this
     */
    public function setProductsValueGross($productsValueGross): self
    {
        $this->productsValueGross = $productsValueGross;
        return $this;
    }


}