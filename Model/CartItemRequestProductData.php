<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Model;

use LSB\UtilityBundle\Value\Value;

class CartItemRequestProductData
{
    /**
     * @var string|null
     */
    protected ?string $message = null;

    public function __construct(
        protected ?string $productUuid,
        protected ?string $productSetUuid,
        protected ?string $orderCode,
        protected ?Value $quantity,
        protected ?Value $productSetQuantity,
        protected bool $isSkipped = false
    ) {}

    /**
     * @return string|null
     */
    public function getProductUuid(): ?string
    {
        return $this->productUuid;
    }

    /**
     * @param string|null $productUuid
     * @return CartItemRequestProductData
     */
    public function setProductUuid(?string $productUuid): CartItemRequestProductData
    {
        $this->productUuid = $productUuid;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getProductSetUuid(): ?string
    {
        return $this->productSetUuid;
    }

    /**
     * @param string|null $productSetUuid
     * @return CartItemRequestProductData
     */
    public function setProductSetUuid(?string $productSetUuid): CartItemRequestProductData
    {
        $this->productSetUuid = $productSetUuid;
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
     * @return CartItemRequestProductData
     */
    public function setOrderCode(?string $orderCode): CartItemRequestProductData
    {
        $this->orderCode = $orderCode;
        return $this;
    }

    /**
     * @return Value|null
     */
    public function getQuantity(): ?Value
    {
        return $this->quantity;
    }

    /**
     * @param Value|null $quantity
     * @return CartItemRequestProductData
     */
    public function setQuantity(?Value $quantity): CartItemRequestProductData
    {
        $this->quantity = $quantity;
        return $this;
    }

    /**
     * @return Value|null
     */
    public function getProductSetQuantity(): ?Value
    {
        return $this->productSetQuantity;
    }

    /**
     * @param Value|null $productSetQuantity
     * @return CartItemRequestProductData
     */
    public function setProductSetQuantity(?Value $productSetQuantity): CartItemRequestProductData
    {
        $this->productSetQuantity = $productSetQuantity;
        return $this;
    }

    /**
     * @return bool
     */
    public function isSkipped(): bool
    {
        return $this->isSkipped;
    }

    /**
     * @param bool $isSkipped
     * @return CartItemRequestProductData
     */
    public function setIsSkipped(bool $isSkipped): CartItemRequestProductData
    {
        $this->isSkipped = $isSkipped;
        return $this;
    }
}