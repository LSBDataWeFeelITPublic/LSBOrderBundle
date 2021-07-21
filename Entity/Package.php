<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Entity;

use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\MappedSuperclass;
use LSB\UtilityBundle\Traits\CreatedUpdatedTrait;
use LSB\UtilityBundle\Traits\UuidTrait;

/**
 * Class OrderPackage
 * @package LSB\OrderBundle\Entity
 * @MappedSuperclass
 */
abstract class Package implements PackageInterface
{
    use UuidTrait;
    use CreatedUpdatedTrait;
    use StatusTrait;
    use TotalValueCostTrait;
    use WeightTrait;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=50)
     */
    protected ?string $number = null;

    /**
     * @var Address
     * @ORM\Embedded(class="LSB\OrderBundle\Entity\Address", columnPrefix="delivery_address_")
     */
    protected Address $deliveryAddress;

    /**
     * OrderPackage constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        $this->generateUuid();
        $this->deliveryAddress = new Address();
    }

    /**
     * @throws \Exception
     */
    public function __clone()
    {
        $this->generateUuid(true);
        $this->id = null;
    }

    /**
     * @return string|null
     */
    public function getNumber(): ?string
    {
        return $this->number;
    }

    /**
     * @param string|null $number
     * @return $this
     */
    public function setNumber(?string $number): static
    {
        $this->number = $number;
        return $this;
    }

    /**
     * @return Address
     */
    public function getDeliveryAddress(): Address
    {
        return $this->deliveryAddress;
    }

    /**
     * @param Address $deliveryAddress
     * @return $this
     */
    public function setDeliveryAddress(Address $deliveryAddress): static
    {
        $this->deliveryAddress = $deliveryAddress;
        return $this;
    }
}
