<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Entity;

use LSB\OrderBundle\Interfaces\TotalValueCostInterface;
use LSB\OrderBundle\Interfaces\WeightInterface;
use LSB\UtilityBundle\Interfaces\Base\BasePackageInterface;
use LSB\UtilityBundle\Interfaces\UuidInterface;

/**
 * Interface PackageInterface
 * @package LSB\OrderBundle\Entity
 */
interface PackageInterface extends UuidInterface, TotalValueCostInterface, WeightInterface
{
    const TYPE_DEFAULT = 10;

    const PACKAGE_TYPE_FROM_LOCAL_STOCK = 10;
    const PACKAGE_TYPE_FROM_REMOTE_STOCK = 20;
    const PACKAGE_TYPE_NEXT_SHIPPING = 30;
    const PACKAGE_TYPE_FROM_SUPPLIER = 40;
    const PACKAGE_TYPE_BACKORDER = 50;

    const BACKORDER_PACKAGE_ITEM_SHIPPING_DAYS = 999;
    const LOCAL_PACKAGE_MAX_SHIPPING_DAYS = 2;
    const PACKAGE_MAX_PERIOD = 1;
    const FIRST_POSITION = 1;
}