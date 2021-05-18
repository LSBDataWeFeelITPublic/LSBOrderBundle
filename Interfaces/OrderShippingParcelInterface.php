<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Interfaces;

/**
 * Interface OrderShippingParcelInterface
 * @package LSB\OrderBundle\Interfaces
 */
interface OrderShippingParcelInterface extends StatusInterface
{
    public function getPosition(): ?int;

    public function setPosition(int $position): self;
}
