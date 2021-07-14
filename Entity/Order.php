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
    use ValueCostTrait;
    use WeightTrait;
    use ViewTokenTrait;
    use ConfirmationTokenTrait;
    use UnmaskTokenTrait;
    use ProcessDateTrait;
    use CalculationTypeTrait;

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
    protected ?ContractorInterface $payerContractor;

    /**
     * @var ContractorInterface|null
     * @ORM\ManyToOne(targetEntity="LSB\ContractorBundle\Entity\ContractorInterface")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected ?ContractorInterface $suggestedPayerContractor;

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
    protected ?int $payerContractorVatStatus;

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
     * @var BillingData
     * @ORM\Embedded(class="LSB\OrderBundle\Entity\BillingData", columnPrefix="billing_data_")
     */
    protected BillingData $billingData;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->orderNotes = new ArrayCollection();
        $this->orderPackages = new ArrayCollection();
        $this->billingData = new BillingData();

        $this->generateUuid();
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
    public function setNumber(?string $number): self
    {
        $this->number = $number;
        return $this;
    }

    /**
     * @return ContractorInterface|null
     */
    public function getPayerContractor(): ?ContractorInterface
    {
        return $this->payerContractor;
    }

    /**
     * @param ContractorInterface|null $payerContractor
     * @return $this
     */
    public function setPayerContractor(?ContractorInterface $payerContractor): self
    {
        $this->payerContractor = $payerContractor;
        return $this;
    }

    /**
     * @return ContractorInterface|null
     */
    public function getSuggestedPayerContractor(): ?ContractorInterface
    {
        return $this->suggestedPayerContractor;
    }

    /**
     * @param ContractorInterface|null $suggestedPayerContractor
     * @return $this
     */
    public function setSuggestedPayerContractor(?ContractorInterface $suggestedPayerContractor): self
    {
        $this->suggestedPayerContractor = $suggestedPayerContractor;
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
    public function setRecipientContractor(?ContractorInterface $recipientContractor): self
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
    public function setRealisationAt(?DateTime $realisationAt): self
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
    public function setCurrency(?CurrencyInterface $currency): self
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
    public function getPayerContractorVatStatus(): ?int
    {
        return $this->payerContractorVatStatus;
    }

    /**
     * @param int|null $payerContractorVatStatus
     * @return $this
     */
    public function setPayerContractorVatStatus(?int $payerContractorVatStatus): self
    {
        $this->payerContractorVatStatus = $payerContractorVatStatus;
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
    public function setVerifiedAt(?DateTime $verifiedAt): self
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
    public function setParentOrder(?Order $parentOrder): self
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
    public function setIsComplaint(bool $isComplaint): self
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
    public function setCalculationType(int $calculationType): self
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
    public function setPackagesCnt(int $packagesCnt): self
    {
        $this->packagesCnt = $packagesCnt;
        return $this;
    }

    /**
     * @return BillingData
     */
    public function getBillingData(): BillingData
    {
        return $this->billingData;
    }

    /**
     * @param BillingData $billingData
     * @return Order
     */
    public function setBillingData(BillingData $billingData): static
    {
        $this->billingData = $billingData;
        return $this;
    }
}
