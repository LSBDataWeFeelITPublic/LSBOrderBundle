<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Entity;

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
abstract class OrderPackage extends Package implements OrderPackageInterface
{
    use ProcessDateTrait;

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
     * @ORM\Column(type="boolean", options={"default" = false})
     */
    protected bool $isStockReserved = false;

    /**
     * OrderPackage constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct();

        $this->generateUuid();
        $this->orderPackageItems = new ArrayCollection();
    }

    /**
     * @param null $num
     * @return array
     */
    public function generateOrderPackageNumber($num = null)
    {
        $orderNumber = $this->getOrder()?->getNumber();

        // nie nadpisujemy istniejącego już numeru
        if (!$this->getNumber() && $orderNumber) {

            // jeżeli nie został przekazany argument $num, ustal wartość atomu P na podstawie istniejących paczek w zamówieniu
            if (!$num) {
                $num = $this->getNextPackageCountNumber();
            }

            $orderPackageNumber = $orderNumber . '/P' . $num;
            $this->setNumber($orderPackageNumber);
        }

        return ['packageNumber' => $this->getNumber(), 'number' => $num];
    }

    /**
     * TODO move to service
     * @return int
     */
    public function getNextPackageCountNumber(): int
    {
        $max_number = null;
        $num = 1;

        $packages = $this->getOrder()->getOrderPackages();
        if ($packages->count()) {

            /**
             * @var OrderPackageInterface $package
             */
            foreach ($packages as $package) {
                $packageNumber = $package->getNumber();
                if ($packageNumber) {
                    $max_number = $packageNumber;
                }
            }

            if ($max_number) {
                $tmpNumArray = explode('/P', trim($max_number));
                if (!empty($tmpNumArray) && array_key_exists(1, $tmpNumArray)) {
                    $num = (int)$tmpNumArray[1] + 1;
                }
            }
        }

        return $num;
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

    /**
     * @return bool
     */
    public function isStockReserved(): bool
    {
        return $this->isStockReserved;
    }

    /**
     * @param bool $isStockReserved
     * @return OrderPackage
     */
    public function setIsStockReserved(bool $isStockReserved): OrderPackage
    {
        $this->isStockReserved = $isStockReserved;
        return $this;
    }
}
