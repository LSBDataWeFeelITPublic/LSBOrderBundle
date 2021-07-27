<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Entity;

use LSB\OrderBundle\Interfaces\OrderStatusInterface;
use LSB\OrderBundle\Interfaces\ProcessDateInterface;
use LSB\OrderBundle\Interfaces\TotalValueCostInterface;
use LSB\OrderBundle\Interfaces\WeightInterface;
use LSB\UtilityBundle\Calculation\CalculationTypeInterface;
use LSB\UtilityBundle\Interfaces\UuidInterface;
use LSB\UtilityBundle\Token\ConfirmationTokenInterface;
use LSB\UtilityBundle\Token\UnmaskTokenInterface;
use LSB\UtilityBundle\Token\ViewTokenInterface;

/**
 * Interface OrderInterface
 * @package LSB\OrderBundle\Entity
 */
interface OrderInterface extends
    UuidInterface,
    TotalValueCostInterface,
    OrderStatusInterface,
    WeightInterface,
    ConfirmationTokenInterface,
    UnmaskTokenInterface,
    ViewTokenInterface,
    ProcessDateInterface,
    CalculationTypeInterface
{
    const PROCESSING_TYPE_DEFAULT = 10; //Domyślny sposób obsługi zamówienia - poprzez
}