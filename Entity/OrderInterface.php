<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Entity;

/**
 * Interface OrderInterface
 * @package LSB\OrderBundle\Entity
 */
interface OrderInterface
{
    const CALCULATION_TYPE_NETTO = 10;
    const CALCULATION_TYPE_GROSS = 20;
}