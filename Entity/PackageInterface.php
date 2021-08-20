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
interface PackageInterface extends BasePackageInterface, UuidInterface, TotalValueCostInterface, WeightInterface
{
    const TYPE_DEFAULT = 10;
}