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

    const FIRST_POSITION = 1;

    const BACKORDER_PACKAGE_ITEM_SHIPPING_DAYS = 99999;
    const ITEM_AVAILABLE_FROM_LOCAL_STOCK = 10;
    const ITEM_AVAILABLE_FROM_REMOTE_STOCK = 20;
    const ITEM_AVAILABLE_FOR_BACKORDER = 40;

}