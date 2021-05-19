<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use LSB\ProductBundle\Entity\ProductInterface;
use LSB\UtilityBundle\Traits\CreatedUpdatedTrait;
use LSB\UtilityBundle\Traits\PositionTrait;
use LSB\UtilityBundle\Traits\UuidTrait;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping\MappedSuperclass;

/**
 * Class OrderPackageItem
 * @package LSB\OrderBundle\Entity
 * @ORM\HasLifecycleCallbacks()
 * @MappedSuperclass
 */
abstract class OrderPackageItem extends PackageItem implements OrderPackageItemInterface
{
    /**
     * @var OrderPackageInterface|null
     * @ORM\ManyToOne(targetEntity="LSB\OrderBundle\Entity\OrderPackageInterface", inversedBy="items")
     */
    protected ?OrderPackageInterface $orderPackage;

    /**
     * @return OrderPackageInterface|null
     */
    public function getOrderPackage(): ?OrderPackageInterface
    {
        return $this->orderPackage;
    }

    /**
     * @param OrderPackageInterface|null $orderPackage
     * @return $this
     */
    public function setOrderPackage(?OrderPackageInterface $orderPackage): self
    {
        $this->orderPackage = $orderPackage;
        return $this;
    }
}