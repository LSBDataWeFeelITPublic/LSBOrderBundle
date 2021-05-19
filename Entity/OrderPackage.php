<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Entity;

use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\MappedSuperclass;
use LSB\UtilityBundle\Traits\CreatedUpdatedTrait;
use LSB\UtilityBundle\Traits\UuidTrait;
use Symfony\Component\Validator\Constraints as Assert;

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

    /**
     * @var string
     * @ORM\Column(type="string", length=50)
     */
    protected string $number;

    /**
     * @ORM\ManyToOne(targetEntity="LSB\OrderBundle\Entity\OrderInterface", inversedBy="orderPackages")
     * @ORM\JoinColumn()
     */
    protected OrderInterface $order;

    /**
     * @var ArrayCollection|Collection|OrderPackageItemInterface
     *
     * @ORM\OneToMany(targetEntity="LSB\OrderBundle\Entity\OrderPackageItemInterface", mappedBy="orderPackage", orphanRemoval=true, cascade={"persist", "remove"})
     * @ORM\JoinColumn(onDelete="CASCADE")
     * @ORM\OrderBy({"position" = "ASC", "id" = "ASC"})
     */
    protected $orderPackageItems;

    /**
     * @var DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\DateTime()
     */
    protected ?DateTime $deliveredAt;

    /**
     * @var float|null
     * @ORM\Column(type="decimal", precision=18, scale=2, nullable=true)
     */
    protected $totalPaymentCostNet;

    /**
     * @var float|null
     * @ORM\Column(type="decimal", precision=18, scale=2, nullable=true)
     */
    protected $totalPaymentCostGross;

    /**
     * @var float|null
     * @ORM\Column(type="decimal", precision=18, scale=2, nullable=true)
     */
    protected $totalShippingNet;

    /**
     * @var float|null
     * @ORM\Column(type="decimal", precision=18, scale=2, nullable=true)
     */
    protected $totalShippingGross;

    /**
     * @var float|null
     * @ORM\Column(type="decimal", precision=18, scale=2, nullable=true)
     */
    protected $totalProductsNet;

    /**
     * @var float|null
     * @ORM\Column(type="decimal", precision=18, scale=2, nullable=true)
     */
    protected $totalProductsGross;

    /**
     * @var float|null
     * @ORM\Column(type="decimal", precision=18, scale=2, nullable=true)
     */
    protected $weight;

    /**
     * @var float|null
     * @ORM\Column(type="decimal", precision=18, scale=2, nullable=true)
     */
    protected $totalProductWeightGross;
}
