<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Entity;

use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\MappedSuperclass;
use Exception;
use LSB\OrderBundle\Traits\StatusTrait;
use LSB\UtilityBundle\Traits\CreatedUpdatedTrait;
use LSB\UtilityBundle\Traits\UuidTrait;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Class OrderPackage
 * @package LSB\OrderBundle\Entity
 * @MappedSuperclass
 */
abstract class OrderPackage implements OrderPackageStatusInterface
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
     * @Groups({"Default", "SHOP_Public"})
     *
     * @var ArrayCollection|Collection|PackageItemInterface
     *
     * @ORM\OneToMany(targetEntity="LSB\OrderBundle\Entity\PackageItemInterface", mappedBy="orderPackage", orphanRemoval=true, cascade={"persist", "remove"})
     * @ORM\JoinColumn(onDelete="CASCADE")
     * @ORM\OrderBy({"position" = "ASC", "id" = "ASC"})
     */
    protected $packageItems;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="LSB\OrderBundle\Interfaces\OrderShippingParcelInterface", mappedBy="shippingPackage", orphanRemoval=true, cascade={"persist", "remove"})
     * @ORM\JoinColumn(onDelete="CASCADE")
     * @ORM\OrderBy({"position" = "ASC", "id" = "ASC"})
     */
    protected $shippingParcels;

    /**
     * @var DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\DateTime()
     */
    protected ?DateTime $shippedAt;

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

    /**
     *
     * @var string|null
     * @ORM\Column(type="string", nullable=true)
     */
    protected $shippingId;

    /**
     * @var array
     * @ORM\Column(type="array", nullable=false, options={"default": "a:0:{}"})
     */
    protected $shippingIds = [];

    /**
     * Nazwa kodowa modułu do obsługi wysyłki (np. dpd)
     *
     * @var string|null
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $shippingModuleName;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Length(max="255")
     */
    protected $spedLabelFileName;

    /**
     * @var boolean|null
     * @ORM\Column(type="boolean", nullable=true, options={"default" = false})
     * @Assert\Type(type="boolean")
     */
    protected $isStockReserved = false;

    /**
     * @var DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $shippingProcessingStartedAt;

    /**
     * @var int|null
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $shippingModuleCommunicationStatus;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="LSB\OrderBundle\Entity\OrderNoteInterface", mappedBy="orderPackage", cascade={"persist", "remove"}, orphanRemoval=true)
     * @ORM\OrderBy({"id" = "ASC"})
     * @Assert\Valid()
     */
    protected $notes;

    /**
     * @var string|null
     *
     * @Groups({"Default", "SHOP_Public"})
     * @ORM\Column(type="text", nullable=true)
     */
    protected $shippingNumber;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=false, options={"default": false})
     */
    protected $isShippingPrepareCancelled = false;

    /**
     * @var DateTime|null
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\DateTime()
     */
    protected $shippingPrepareCancelledAt;

    /**
     * Wartość kwoty pobrania
     *
     * @var float|null
     * @ORM\Column(type="decimal", precision=18, scale=2, nullable=true)
     */
    protected $codValue;

    /**
     * Flaga blokująca przekazanie kwoty za pobraniem do kuriera
     *
     * @var boolean
     * @ORM\Column(type="boolean", nullable=false, options={"default": false})
     */
    protected $isCodValueBlocked = false;
}
