<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Interfaces;

/**
 * Interface ValueCostInterface
 * @package LSB\OrderBundle\Interfaces
 */
interface ValueCostInterface
{
    /**
     * @return float|null
     */
    public function getTotalValueNet(): ?float;

    /**
     * @param float|string|null $totalValueNet
     * @return $this
     */
    public function setTotalValueNet(float|string|null $totalValueNet): self;

    /**
     * @return float|null
     */
    public function getTotalValueGross(): ?float;

    /**
     * @param float|string|null $totalValueGross
     * @return $this
     */
    public function setTotalValueGross(float|string|null $totalValueGross): self;

    /**
     * @return float|null
     */
    public function getShippingCostNet(): ?float;

    /**
     * @param float|string|null $shippingCostNet
     * @return $this
     */
    public function setShippingCostNet(float|string|null $shippingCostNet): self;

    /**
     * @return float|null
     */
    public function getShippingCostGross(): ?float;

    /**
     * @param float|string|null $shippingCostGross
     * @return $this
     */
    public function setShippingCostGross(float|string|null $shippingCostGross): self;

    /**
     * @return int|null
     */
    public function getShippingCostTaxRate(): ?int;

    /**
     * @param int|null $shippingCostTaxRate
     * @return $this
     */
    public function setShippingCostTaxRate(?int $shippingCostTaxRate): self;

    /**
     * @return int|null
     */
    public function getPaymentCostTaxRate(): ?int;

    /**
     * @param int|null $paymentCostTaxRate
     * @return $this
     */
    public function setPaymentCostTaxRate(?int $paymentCostTaxRate): self;

    /**
     * @return float|string|null
     */
    public function getPaymentCostNet();

    /**
     * @param float|string|null $paymentCostNet
     * @return $this
     */
    public function setPaymentCostNet(float|string|null $paymentCostNet): self;

    /**
     * @return float|null
     */
    public function getPaymentCostGross(): ?float;

    /**
     * @param float|string|null $paymentCostGross
     * @return $this
     */
    public function setPaymentCostGross(float|string|null $paymentCostGross): static;

    /**
     * @return float|null
     */
    public function getProductsValueNet(): ?float;

    /**
     * @param float|string|null $productsValueNet
     * @return $this
     */
    public function setProductsValueNet(float|string|null $productsValueNet): self;

    /**
     * @return float|null
     */
    public function getProductsValueGross(): ?float;

    /**
     * @param float|string|null $productsValueGross
     * @return $this
     */
    public function setProductsValueGross(float|string|null $productsValueGross): static;
}