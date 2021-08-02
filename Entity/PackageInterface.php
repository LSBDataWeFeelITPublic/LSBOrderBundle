<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Entity;

use LSB\OrderBundle\Interfaces\TotalValueCostInterface;
use LSB\OrderBundle\Interfaces\WeightInterface;
use LSB\UtilityBundle\Interfaces\UuidInterface;

/**
 * Interface PackageInterface
 * @package LSB\OrderBundle\Entity
 */
interface PackageInterface extends UuidInterface, TotalValueCostInterface, WeightInterface
{
    const FIRST_POSITION = 1;

    const TYPE_DEFAULT = 10;

    const PACKAGE_TYPE_FROM_LOCAL_STOCK = 10;
    const PACKAGE_TYPE_FROM_REMOTE_STOCK = 20;
    const PACKAGE_TYPE_NEXT_SHIPPING = 30;
    const PACKAGE_TYPE_FROM_SUPPLIER = 40;
    const PACKAGE_TYPE_BACKORDER = 50;
}