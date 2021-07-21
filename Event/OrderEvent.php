<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Event;

use LSB\OrderBundle\Entity\OrderInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class OrderEvent
 * @package LSB\OrderBundle\Event
 */
class OrderEvent extends Event
{
    public function __construct(OrderInterface $order) {}
}