<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\MappedSuperclass;

/**
 * Class CartPackageItem
 * @package LSB\OrderBundle\Entity
 * @ORM\HasLifecycleCallbacks()
 * @MappedSuperclass
 */
abstract class CartPackageItem extends PackageItem implements CartPackageItemInterface
{
    /**
     * @var CartPackageInterface|null
     * @ORM\ManyToOne(targetEntity="LSB\OrderBundle\Entity\CartPackageInterface", inversedBy="items")
     */
    protected ?CartPackageInterface $cartPackage;

    /**
     * @return CartPackageInterface|null
     */
    public function getCartPackage(): ?CartPackageInterface
    {
        return $this->cartPackage;
    }

    /**
     * @param CartPackageInterface|null $cartPackage
     * @return $this
     */
    public function setCartPackage(?CartPackageInterface $cartPackage): static
    {
        $this->cartPackage = $cartPackage;
        return $this;
    }


}
