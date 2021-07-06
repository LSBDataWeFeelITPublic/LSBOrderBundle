<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Entity;

use LSB\UtilityBundle\Interfaces\PositionInterface;

/**
 * Interface PackageItemInterface
 * @package LSB\OrderBundle\Entity
 */
interface PackageItemInterface extends PositionInterface
{
    const TYPE_DEFAULT = 10;
    const TYPE_SHIPPING = 100;
    const TYPE_PAYMENT = 110;

    const PRODUCT_TYPE_DEFAULT = 10;
}