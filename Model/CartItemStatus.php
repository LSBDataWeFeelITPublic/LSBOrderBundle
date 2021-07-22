<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Model;

use LSB\OrderBundle\Entity\CartItemInterface;
use JMS\Serializer\Annotation\Groups;

/**
 * Class CartItemStatus
 * @package LSB\OrderBundle\Model
 */
class CartItemStatus
{
    /**
     * @var CartItemInterface|null
     */
    protected ?CartItemInterface $cartItem;

    /**
     * @var bool
     */
    protected bool $isChecked = false;

    /**
     * CartItemStatus constructor.
     * @param bool $isChecked
     * @param CartItemInterface|null $cartItem
     */
    public function __construct(bool $isChecked = false, ?CartItemInterface $cartItem = null)
    {
        $this->isChecked = $isChecked;
        $this->cartItem = $cartItem;
    }

    /**
     * @return CartItemInterface|null
     */
    public function getCartItem(): ?CartItemInterface
    {
        return $this->cartItem;
    }

    /**
     * @param CartItemInterface|null $cartItem
     * @return CartItemStatus
     */
    public function setCartItem(?CartItemInterface $cartItem): CartItemStatus
    {
        $this->cartItem = $cartItem;
        return $this;
    }

    /**
     * @return bool
     */
    public function isChecked(): bool
    {
        return $this->isChecked;
    }

    /**
     * @param bool $isChecked
     * @return CartItemStatus
     */
    public function setIsChecked(bool $isChecked): CartItemStatus
    {
        $this->isChecked = $isChecked;
        return $this;
    }
}
