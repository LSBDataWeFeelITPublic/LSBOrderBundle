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
abstract class OrderPackage implements OrderPackageInterface
{
    use UuidTrait;
    use CreatedUpdatedTrait;
    use StatusTrait;
    use ValueCostTrait;
    use WeightTrait;
    use ProcessDateTrait;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=50)
     */
    protected ?string $number = null;

    /**
     * @var OrderInterface|null
     * @ORM\ManyToOne(targetEntity="LSB\OrderBundle\Entity\OrderInterface", inversedBy="orderPackages")
     * @ORM\JoinColumn()
     */
    protected ?OrderInterface $order = null;

    /**
     * @var ArrayCollection|Collection|OrderPackageItemInterface
     *
     * @ORM\OneToMany(targetEntity="LSB\OrderBundle\Entity\OrderPackageItemInterface", mappedBy="orderPackage", orphanRemoval=true, cascade={"persist", "remove"})
     * @ORM\JoinColumn(onDelete="CASCADE")
     * @ORM\OrderBy({"position" = "ASC", "id" = "ASC"})
     */
    protected Collection $orderPackageItems;

    /**
     * OrderPackage constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        $this->generateUuid();
        $this->orderPackageItems = new ArrayCollection();
    }

    /**
     * @return Collection
     */
    public function getShippingTypeOrderPackageItems(): Collection
    {
        return $this->getTypeOrderPackageItems(OrderPackageItemInterface::TYPE_SHIPPING);
    }

    /**
     * @return Collection
     */
    public function getPaymentTypeOrderPackageItems(): Collection
    {
        return $this->getTypeOrderPackageItems(OrderPackageItemInterface::TYPE_PAYMENT);
    }

    /**
     * @return Collection
     */
    public function getDefaultTypeOrderPackageItems(): Collection
    {
        return $this->getTypeOrderPackageItems(OrderPackageItemInterface::TYPE_DEFAULT);
    }

    /**
     * @param int $type
     * @return Collection
     */
    protected function getTypeOrderPackageItems(int $type): Collection
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq("type", $type))
            ->orderBy(['id' => Criteria::ASC]);

        return $this->orderPackageItems->matching($criteria);
    }

    /**
     * @throws \Exception
     */
    public function __clone()
    {
        $this->id = null;
        $this->generateUuid(true);
    }

    /**
     * @return string|null
     */
    public function getNumber(): string|null
    {
        return $this->number;
    }

    /**
     * @param string|null $number
     * @return OrderPackage
     */
    public function setNumber(?string $number): OrderPackage
    {
        $this->number = $number;
        return $this;
    }

    /**
     * @return OrderInterface|null
     */
    public function getOrder(): OrderInterface|null
    {
        return $this->order;
    }

    /**
     * @param OrderInterface|null $order
     * @return OrderPackage
     */
    public function setOrder(?OrderInterface $order): OrderPackage
    {
        $this->order = $order;
        return $this;
    }

    /**
     * @return ArrayCollection|Collection|OrderPackageItemInterface
     */
    public function getOrderPackageItems(): ArrayCollection|Collection|OrderPackageItemInterface
    {
        return $this->orderPackageItems;
    }

    /**
     * @param ${ENTRY_HINT} $orderPackageItem
     *
     * @return OrderPackage
     */
    public function addOrderPackageItem($orderPackageItem): OrderPackage
    {
        if (false === $this->orderPackageItems->contains($orderPackageItem)) {
            $this->orderPackageItems->add($orderPackageItem);
        }
        return $this;
    }

    /**
     * @param ${ENTRY_HINT} $orderPackageItem
     *
     * @return OrderPackage
     */
    public function removeOrderPackageItem($orderPackageItem): OrderPackage
    {
        if (true === $this->orderPackageItems->contains($orderPackageItem)) {
            $this->orderPackageItems->removeElement($orderPackageItem);
        }
        return $this;
    }

    /**
     * @param ArrayCollection|Collection|OrderPackageItemInterface $orderPackageItems
     * @return OrderPackage
     */
    public function setOrderPackageItems(ArrayCollection|Collection|OrderPackageItemInterface $orderPackageItems): OrderPackage
    {
        $this->orderPackageItems = $orderPackageItems;
        return $this;
    }
}
