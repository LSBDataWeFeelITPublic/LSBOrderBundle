<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Entity;

use Doctrine\Common\Collections\Collection;
use LSB\OrderBundle\Interfaces\OrderPackageStatusInterface;
use LSB\OrderBundle\Interfaces\ValueCostInterface;
use LSB\UtilityBundle\Interfaces\UuidInterface;

/**
 * Interface OrderPackageInterface
 * @package LSB\OrderBundle\Entity
 */
interface OrderPackageInterface extends UuidInterface, ValueCostInterface, OrderPackageStatusInterface
{
    public function getShippingTypeOrderPackageItems(): Collection;

    public function getPaymentTypeOrderPackageItems(): Collection;

    public function getDefaultTypeOrderPackageItems(): Collection;
}