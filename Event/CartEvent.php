<?php
declare(strict_types=1);

namespace LSB\CartBundle\Event;

use LSB\OrderBundle\CartModule\CartModuleInterface;
use LSB\OrderBundle\Entity\CartInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class CartEvent
 * @package LSB\CartBundle\Event
 */
class CartEvent extends Event
{
    protected ?CartInterface $cart;

    protected ?CartModuleInterface $module;

    /**
     * CartEvent constructor.
     * @param CartInterface $cart
     * @param CartModuleInterface|null $module
     */
    public function __construct(
        CartInterface $cart,
        ?CartModuleInterface $module = null
    ) {
        $this->cart = $cart;
        $this->module = $module;
    }

    /**
     * @return CartInterface|null
     */
    public function getCart(): ?CartInterface
    {
        return $this->cart;
    }

    /**
     * @param CartInterface|null $cart
     * @return CartEvent
     */
    public function setCart(?CartInterface $cart): CartEvent
    {
        $this->cart = $cart;
        return $this;
    }

    /**
     * @return CartModuleInterface|null
     */
    public function getModule(): ?CartModuleInterface
    {
        return $this->module;
    }

    /**
     * @param CartModuleInterface|null $module
     * @return CartEvent
     */
    public function setModule(?CartModuleInterface $module): CartEvent
    {
        $this->module = $module;
        return $this;
    }
}
