<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Entity;

use Doctrine\Common\Collections\Collection;
use LSB\OrderBundle\Interfaces\OrderPackageStatusInterface;

interface OrderPackageInterface extends PackageInterface, OrderPackageStatusInterface
{
    public function getShippingTypeOrderPackageItems(): Collection;

    public function getPaymentTypeOrderPackageItems(): Collection;

    public function getDefaultTypeOrderPackageItems(): Collection;
}