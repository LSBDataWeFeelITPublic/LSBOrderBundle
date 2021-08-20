<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Entity;

use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\MappedSuperclass;
use LSB\ContractorBundle\Entity\ContractorInterface;
use LSB\LocaleBundle\Entity\CurrencyInterface;
use LSB\OrderBundle\Interfaces\OrderStatusInterface;
use LSB\UtilityBundle\Calculation\CalculationTypeTrait;
use LSB\UtilityBundle\Token\ConfirmationTokenTrait;
use LSB\UtilityBundle\Token\UnmaskTokenTrait;
use LSB\UtilityBundle\Token\ViewTokenTrait;
use LSB\UtilityBundle\Traits\CreatedUpdatedTrait;
use LSB\UtilityBundle\Traits\UuidTrait;
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
abstract class Order implements OrderInterface
{
    use UuidTrait;
    use CreatedUpdatedTrait;
    use TotalValueCostTrait;
    use WeightTrait;
    use ViewTokenTrait;
    use ConfirmationTokenTrait;
    use UnmaskTokenTrait;
    use ProcessDateTrait;
    use CalculationTypeTrait;
    use AddressTrait;
    use TermsTrait;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=50, nullable=true)
     * @Assert\Length(max=50)
     */
    protected ?string $number;

    /**
     * @var ContractorInterface|null
     * @ORM\ManyToOne(targetEntity="LSB\ContractorBundle\Entity\ContractorInterface")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected ?ContractorInterface $billingContractor;

    /**
     * @var ContractorInterface|null
     * @ORM\ManyToOne(targetEntity="LSB\ContractorBundle\Entity\ContractorInterface")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected ?ContractorInterface $suggestedBillingContractor;

    /**
     * @ORM\ManyToOne(targetEntity="LSB\ContractorBundle\Entity\ContractorInterface")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    protected ?ContractorInterface $recipientContractor;

    /**
     * @var ArrayCollection|Collection|OrderNoteInterface[]
     * @ORM\OneToMany(targetEntity="LSB\OrderBundle\Entity\OrderNoteInterface", mappedBy="order", cascade={"persist", "remove"}, orphanRemoval=true)
     * @ORM\OrderBy({"id" = "ASC"})
     * @Assert\Valid()
     */
    protected Collection $orderNotes;

    /**
     * @var CurrencyInterface|null
     * @ORM\ManyToOne(targetEntity="LSB\LocaleBundle\Entity\CurrencyInterface")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected ?CurrencyInterface $currency = null;

    /**
     * @ORM\OneToMany(targetEntity="LSB\OrderBundle\Entity\OrderPackageInterface", mappedBy="order", orphanRemoval=true, cascade={"persist", "remove"})
     * @ORM\OrderBy({"id" = "ASC"})
     */
    protected Collection $orderPackages;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected ?int $billingContractorVatStatus;

    /**
     * @ORM\ManyToOne(targetEntity="LSB\OrderBundle\Entity\OrderInterface")
     * @ORM\JoinColumn(name="parent_order_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected ?self $parentOrder;

    /**
     * @var bool
     * @ORM\Column(type="boolean", options={"default": false})
     */
    protected bool $isComplaint = false;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=false)
     */
    protected int $packagesCnt = 0;

    /**
     * Sposób przetwarzania
     *
     * @var integer
     * @ORM\Column(type="integer", nullable=true, options={"default": 1})
     * @Assert\NotBlank(groups={"EdiCartProcessing"})
     */
    protected int $processingType = self::PROCESSING_TYPE_DEFAULT;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->generateUuid();
        $this->orderNotes = new ArrayCollection();
        $this->orderPackages = new ArrayCollection();

        $this->addressConstruct();
        $this->termsConstruct();
    }

    /**
     * @throws \Exception
     */
    public function __clone()
    {
        $this->id = null;
        $this->number = null;
        $this->generateUuid(true);
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
     * @return ContractorInterface|null
     */
    public function getBillingContractor(): ?ContractorInterface
    {
        return $this->billingContractor;
    }

    /**
     * @param ContractorInterface|null $billingContractor
     * @return $this
     */
    public function setBillingContractor(?ContractorInterface $billingContractor): static
    {
        $this->billingContractor = $billingContractor;
        return $this;
    }

    /**
     * @return ContractorInterface|null
     */
    public function getSuggestedBillingContractor(): ?ContractorInterface
    {
        return $this->suggestedBillingContractor;
    }

    /**
     * @param ContractorInterface|null $suggestedBillingContractor
     * @return $this
     */
    public function setSuggestedBillingContractor(?ContractorInterface $suggestedBillingContractor): static
    {
        $this->suggestedBillingContractor = $suggestedBillingContractor;
        return $this;
    }

    /**
     * @return $thisContractorInterface|null
     */
    public function getRecipientContractor(): ?ContractorInterface
    {
        return $this->recipientContractor;
    }

    /**
     * @param ContractorInterface|null $recipientContractor
     * @return $this
     */
    public function setRecipientContractor(?ContractorInterface $recipientContractor): static
    {
        $this->recipientContractor = $recipientContractor;
        return $this;
    }

    /**
     * @return ArrayCollection|Collection|OrderNoteInterface[]
     */
    public function getOrderNotes()
    {
        return $this->orderNotes;
    }

    /**
     * @param OrderNoteInterface $orderNote
     *
     * @return $this
     */
    public function addOrderNote(OrderNoteInterface $orderNote)
    {
        if (false === $this->orderNotes->contains($orderNote)) {
            $this->orderNotes->add($orderNote);
        }
        return $this;
    }

    /**
     * @param OrderNoteInterface $orderNote
     *
     * @return $this
     */
    public function removeOrderNote(OrderNoteInterface $orderNote)
    {
        if (true === $this->orderNotes->contains($orderNote)) {
            $this->orderNotes->removeElement($orderNote);
        }
        return $this;
    }

    /**
     * @param ArrayCollection|Collection|OrderNoteInterface[] $orderNotes
     * @return $this
     */
    public function setOrderNotes($orderNotes)
    {
        $this->orderNotes = $orderNotes;
        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getRealisationAt(): ?DateTime
    {
        return $this->realisationAt;
    }

    /**
     * @param DateTime|null $realisationAt
     * @return $this
     */
    public function setRealisationAt(?DateTime $realisationAt): static
    {
        $this->realisationAt = $realisationAt;
        return $this;
    }

    /**
     * @return CurrencyInterface|null
     */
    public function getCurrency(): ?CurrencyInterface
    {
        return $this->currency;
    }

    /**
     * @param CurrencyInterface|null $currency
     * @return $this
     */
    public function setCurrency(?CurrencyInterface $currency): static
    {
        $this->currency = $currency;
        return $this;
    }

    /**
     * @return ArrayCollection|Collection
     */
    public function getOrderPackages()
    {
        return $this->orderPackages;
    }

    /**
     * @param ${ENTRY_HINT} $orderPackage
     *
     * @return $this
     */
    public function addOrderPackage($orderPackage)
    {
        if (false === $this->orderPackages->contains($orderPackage)) {
            $this->orderPackages->add($orderPackage);
        }
        return $this;
    }

    /**
     * @param ${ENTRY_HINT} $orderPackage
     *
     * @return $this
     */
    public function removeOrderPackage($orderPackage)
    {
        if (true === $this->orderPackages->contains($orderPackage)) {
            $this->orderPackages->removeElement($orderPackage);
        }
        return $this;
    }

    /**
     * @param ArrayCollection|Collection $orderPackages
     * @return $this
     */
    public function setOrderPackages($orderPackages)
    {
        $this->orderPackages = $orderPackages;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getBillingContractorVatStatus(): ?int
    {
        return $this->billingContractorVatStatus;
    }

    /**
     * @param int|null $billingContractorVatStatus
     * @return $this
     */
    public function setBillingContractorVatStatus(?int $billingContractorVatStatus): static
    {
        $this->billingContractorVatStatus = $billingContractorVatStatus;
        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getVerifiedAt(): ?DateTime
    {
        return $this->verifiedAt;
    }

    /**
     * @param DateTime|null $verifiedAt
     * @return $this
     */
    public function setVerifiedAt(?DateTime $verifiedAt): static
    {
        $this->verifiedAt = $verifiedAt;
        return $this;
    }

    /**
     * @return $this|null
     */
    public function getParentOrder(): ?Order
    {
        return $this->parentOrder;
    }

    /**
     * @param Order|null $parentOrder
     * @return $this
     */
    public function setParentOrder(?Order $parentOrder): static
    {
        $this->parentOrder = $parentOrder;
        return $this;
    }

    /**
     * @return bool
     */
    public function isComplaint(): bool
    {
        return $this->isComplaint;
    }

    /**
     * @param bool $isComplaint
     * @return $this
     */
    public function setIsComplaint(bool $isComplaint): static
    {
        $this->isComplaint = $isComplaint;
        return $this;
    }

    /**
     * @return int
     */
    public function getCalculationType(): int
    {
        return $this->calculationType;
    }

    /**
     * @param int $calculationType
     * @return $this
     */
    public function setCalculationType(int $calculationType): static
    {
        $this->calculationType = $calculationType;
        return $this;
    }

    /**
     * @return int
     */
    public function getPackagesCnt(): int
    {
        return $this->packagesCnt;
    }

    /**
     * @param int $packagesCnt
     * @return $this
     */
    public function setPackagesCnt(int $packagesCnt): static
    {
        $this->packagesCnt = $packagesCnt;
        return $this;
    }
}
