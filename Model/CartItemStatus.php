<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Model;

use LSB\CartBundle\Entity\CartItem;
use JMS\Serializer\Annotation\Groups;

/**
 * Class CartItemStatus
 * @package LSB\OrderBundle\Model
 */
class CartItemStatus
{
    /**
     * @var CartItem|null
     */
    protected $cartItem;

    /**
     * @var bool
     * @Groups(groups={"EDI_User"})
     */
    protected $isChecked = false;

    /**
     * CartItemStatus constructor.
     * @param bool $isChecked
     * @param CartItem|null $cartItem
     */
    public function __construct(bool $isChecked = false, ?CartItem $cartItem = null)
    {
        $this->isChecked = $isChecked;
        $this->cartItem = $cartItem;
    }

    /**
     * @return CartItem|null
     */
    public function getCartItem(): ?CartItem
    {
        return $this->cartItem;
    }

    /**
     * @return bool
     */
    public function isChecked(): bool
    {
        return $this->isChecked;
    }
}
