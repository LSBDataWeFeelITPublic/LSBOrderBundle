<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Entity;

use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\MappedSuperclass;
use LSB\OrderBundle\Interfaces\OrderApplicationInterface;
use LSB\OrderBundle\Interfaces\OrderCartInterface;
use LSB\OrderBundle\Interfaces\OrderContractorInterface;
use LSB\OrderBundle\Interfaces\OrderCurrencyInterface;
use LSB\OrderBundle\Interfaces\OrderPaymentMethodInterface;
use LSB\OrderBundle\Interfaces\OrderStatusInterface;
use LSB\UtilityBundle\Traits\CreatedUpdatedTrait;
use LSB\UtilityBundle\Traits\UuidTrait;
use LSB\UtilityBundle\Translatable\TranslatableTrait;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class Order
 * @package LSB\OrderBundle\Entity
 * @UniqueEntity("number")
 * @MappedSuperclass
 */
abstract class Order implements OrderInterface, OrderStatusInterface
{
    use UuidTrait;
    use CreatedUpdatedTrait;


    /**
     * @var string|null
     * @ORM\Column(type="string", length=50, nullable=true)
     * @Assert\Length(max=50)
     */
    protected ?string $number;

    /**
     * @var OrderContractorInterface|null
     * @ORM\ManyToOne(targetEntity="LSB\OrderBundle\Entity\OrderContractorInterface")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected ?OrderContractorInterface $payerContractor;

    /**
     * @ORM\ManyToOne(targetEntity="LSB\OrderBundle\Entity\OrderContractorInterface")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected ?OrderContractorInterface $suggestedPayerContractor;


    /**
     * @var ArrayCollection|Collection|OrderNoteInterface[]
     * @ORM\OneToMany(targetEntity="OrderNoteInterface", mappedBy="order", cascade={"persist", "remove"}, orphanRemoval=true)
     * @ORM\OrderBy({"id" = "ASC"})
     * @Assert\Valid()
     */
    protected Collection $orderNotes;


    /**
     * @var DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected ?DateTime $realisationAt;

    /**
     * @ORM\ManyToOne(targetEntity="LSB\OrderBundle\Interfaces\OrderCurrencyInterface")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected ?OrderCurrencyInterface $currency;


    /**
     * @ORM\OneToMany(targetEntity="LSB\OrderBundle\Entity\OrderPackageInterface", mappedBy="order", orphanRemoval=true, cascade={"persist", "remove"})
     * @ORM\OrderBy({"id" = "ASC"})
     * @GDPR\Anonymize(type="collection")
     */
    protected Collection $orderPackages;

    /**
     * @ORM\ManyToOne(targetEntity="LSB\OrderBundle\Interfaces\OrderCartInterface", inversedBy="orders", cascade={"remove"})
     */
    protected ?OrderCartInterface $cart;

    /**
     * @Assert\Length(max=255)
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected ?string $invoiceEmail;

    /**
     * @ORM\ManyToOne(targetEntity="LSB\OrderBundle\Interfaces\OrderPaymentMethodInterface")
     * @ORM\JoinColumn()
     */
    protected ?OrderPaymentMethodInterface $paymentMethod;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=120, nullable=true)
     * @Assert\Length(max="120")
     */
    protected ?string $viewToken;

    /**
     * @var DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\DateTime
     */
    protected ?DateTime $viewTokenGeneratedAt;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=120, nullable=true)
     * @Assert\Length(max="120")
     */
    protected $unmaskToken;

    /**
     * @var DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\DateTime
     */
    protected ?DateTime $unmaskTokenGeneratedAt;

    /**
     * @ORM\Column(type="string", length=120, nullable=true)
     * @Assert\Length(max="120")
     */
    protected ?string $confirmationToken;

    /**
     * @var DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\DateTime
     */
    protected ?DateTime $confirmationTokenGeneratedAt;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected ?int $defferedPaymentDays;

    /**
     * @var OrderApplicationInterface
     * @ORM\ManyToOne(targetEntity="LSB\OrderBundle\Entity\OrderApplicationInterface")
     */
    protected OrderApplicationInterface $contextApplication;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected ?int $payerContractorVatStatus;

    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    protected int $vatCalculationType;

    /**
     * @var integer
     * @ORM\Column(type="integer", nullable=false, options={"default": 1})
     */
    protected $orderVerificationStatus;

    /**
     * @var DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected ?DateTime $confirmedAt;

    /**
     * @var DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $verifiedAt;

//    /**
//     * @var Collection
//     * @ORM\OneToMany(targetEntity="LSB\OrderBundle\Interfaces\OrderProformaInvoiceInterface", mappedBy="order", cascade={"persist", "remove"}, orphanRemoval=true)
//     * @ORM\OrderBy({"id" = "ASC"})
//     */
//    protected Collection $proformaInvoices;

    /**
     * @ORM\ManyToOne(targetEntity="LSB\OrderBundle\Entity\OrderInterface")
     * @ORM\JoinColumn(name="parent_order_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected ?self $parentOrder;


    /**
     * @ORM\ManyToOne(targetEntity="LSB\OrderBundle\Interfaces\OrderContractorInterface")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    protected ?OrderContractorInterface $recipientContractor;

    /**
     * @var Collection
     * @ORM\OneToMany(targetEntity="LSB\OrderBundle\Interfaces\OrderNotificationInterface", mappedBy="order")
     * @ORM\OrderBy({"id" = "ASC"})
     */
    protected Collection $notifications;


    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false, options={"default": false})
     */
    protected bool $isComplaint = false;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=false, options={"default": 10})
     */
    protected int $calculationType = self::CALCULATION_TYPE_NETTO;

    /**
     * Licznik paczek (wydzielona kolumna z uwagi na DT)
     *
     * @var int
     * @ORM\Column(type="integer", nullable=false)
     */
    protected int $packagesCnt = 0;

    /**
     * @var int
     * @ORM\Column(type="boolean", options={"default": false})
     */
    protected int $payerContractorDataChangesStatus;

    /**
     * @var DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected ?DateTime $payerContractorDataChangesIgnoredAt;

    /**
     * @var bool
     */
    protected bool $isDataMasked = false;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->status = self::STATUS_OPEN;
        $realisationAt = new DateTime('NOW');
        $realisationAt->add(new \DateInterval('P1D'));
        $this->realisationAt = $realisationAt;

        $this->proformaInvoices = new ArrayCollection();
        $this->orderNotes = new ArrayCollection();
        $this->orderPackages = new ArrayCollection();
        $this->notifications = new ArrayCollection();

        $this->generateUuid();
    }

    public function __clone()
    {
        $this->id = null;
        $this->number = null;
        $this->notifications = new ArrayCollection();


        if ($this->getState()) {
            $this->setState(['open' => 1]);
        }

//        $packagesCloned = new ArrayCollection();
//
//        /**
//         * @var OrderPackage $package
//         */
//        foreach ($this->getOrderPackages() as $package) {
//            $packageClone = clone $package;
//            $packageClone->setOrder($this);
//            $packagesCloned->add($packageClone);
//        }
//        $this->packages = $packagesCloned;
//
//        if ($this->getNotes()->count()) {
//            $orderNotesCloned = new ArrayCollection();
//            foreach ($this->getNotes() as $note) {
//                $noteClone = clone $note;
//                $noteClone->setOrder($this);
//                $orderNotesCloned->add($noteClone);
//            }
//            $this->notes = $orderNotesCloned;
//        }
    }


}
