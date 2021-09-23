<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use LSB\ProductBundle\Entity\SupplierInterface;
use LSB\UtilityBundle\Traits\CreatedUpdatedTrait;
use LSB\UtilityBundle\Traits\UuidTrait;
use LSB\ShippingBundle\Entity\MethodInterface as ShippingMethodInterface;

/**
 * Class OrderPackage
 * @package LSB\OrderBundle\Entity
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
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    protected ?string $number = null;

    /**
     * @var Address
     * @ORM\Embedded(class="LSB\OrderBundle\Entity\Address", columnPrefix="delivery_address_")
     */
    protected Address $deliveryAddress;

    /**
     * @var Address
     * @ORM\Embedded(class="LSB\OrderBundle\Entity\Address", columnPrefix="contact_person_address_")
     */
    protected Address $contactPersonAddress;

    /**
     * @var SupplierInterface|null
     * @ORM\ManyToOne(targetEntity="LSB\ProductBundle\Entity\SupplierInterface")
     */
    protected ?SupplierInterface $supplier = null;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false, options={"default": true})
     */
    protected bool $isChargedForShipping = true;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=false, options={"default": 10})
     */
    protected int $type = self::TYPE_FROM_LOCAL_STOCK;

    /**
     * @var string|null
     * @ORM\Column(type="text", nullable=true)
     */
    protected ?string $deliveryNote;

    /**
     * @var ShippingMethodInterface|null
     *
     * @ORM\ManyToOne(targetEntity="LSB\ShippingBundle\Entity\MethodInterface", fetch="EAGER")
     * @ORM\JoinColumn()
     */
    protected ?ShippingMethodInterface $shippingMethod = null;

    /**
     * @var integer|null
     * @ORM\Column(type="integer", nullable=true)
     */
    protected ?int $shippingDays = null;

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

    /**
     * @return Address
     */
    public function getContactPersonAddress(): Address
    {
        return $this->contactPersonAddress;
    }

    /**
     * @param Address $contactPersonAddress
     * @return $this
     */
    public function setContactPersonAddress(Address $contactPersonAddress): static
    {
        $this->contactPersonAddress = $contactPersonAddress;
        return $this;
    }

    /**
     * @return SupplierInterface|null
     */
    public function getSupplier(): ?SupplierInterface
    {
        return $this->supplier;
    }

    /**
     * @param SupplierInterface|null $supplier
     * @return $this
     */
    public function setSupplier(?SupplierInterface $supplier): static
    {
        $this->supplier = $supplier;
        return $this;
    }

    /**
     * @return bool
     */
    public function isChargedForShipping(): bool
    {
        return $this->isChargedForShipping;
    }

    /**
     * @param bool $isChargedForShipping
     * @return $this
     */
    public function setIsChargedForShipping(bool $isChargedForShipping): static
    {
        $this->isChargedForShipping = $isChargedForShipping;
        return $this;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @param int $type
     * @return $this
     */
    public function setType(int $type): static
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getDeliveryNote(): ?string
    {
        return $this->deliveryNote;
    }

    /**
     * @param string|null $deliveryNote
     * @return $this
     */
    public function setDeliveryNote(?string $deliveryNote): static
    {
        $this->deliveryNote = $deliveryNote;
        return $this;
    }

    /**
     * @return ShippingMethodInterface|null
     */
    public function getShippingMethod(): ?ShippingMethodInterface
    {
        return $this->shippingMethod;
    }

    /**
     * @param ShippingMethodInterface|null $shippingMethod
     * @return $this
     */
    public function setShippingMethod(?ShippingMethodInterface $shippingMethod): static
    {
        $this->shippingMethod = $shippingMethod;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getShippingDays(): ?int
    {
        return $this->shippingDays;
    }

    /**
     * @param int|null $shippingDays
     * @return $this
     */
    public function setShippingDays(?int $shippingDays): static
    {
        $this->shippingDays = $shippingDays;
        return $this;
    }
}