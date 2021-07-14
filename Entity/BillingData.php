<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Embeddable()
 */
class BillingData extends Address
{
    const TYPE_PRIVATE = 10;
    const TYPE_COMPANY = 20;
    const TYPE_BUDGET_UNIT = 30;

    /**
     * @var array|string[]
     */
    public static array $typeList = [
        self::TYPE_PRIVATE => 'Contractor.Private',
        self::TYPE_COMPANY => 'Contractor.Company',
        self::TYPE_BUDGET_UNIT => 'Contractor.BudgetUnit'
    ];

    /**
     * @var string|null
     * @ORM\Column(type="string", length=50, nullable=true)
     * @Assert\Length(max=50)
     */
    protected ?string $taxNumber = null;

    /**
     * @var integer|null
     * @ORM\Column(type="integer", nullable=true, options={"default": 20})
     * @Assert\Length(max=20)
     */
    protected ?int $type = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Length(max=255)
     */
    protected ?string $number = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=500, nullable=true)
     * @Assert\Length(max=500)
     */
    protected ?string $name = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=100, nullable=true)
     * @Assert\Length(max=100)
     */
    protected ?string $shortName = null;

    /**
     * @var bool|null
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected ?bool $isTaxNumberValid = null;

    /**
     * @var bool|null
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected ?bool $isVatUEActivePayer = null;

    /**
     * @return string|null
     */
    public function getTaxNumber(): ?string
    {
        return $this->taxNumber;
    }

    /**
     * @param string|null $taxNumber
     * @return BillingData
     */
    public function setTaxNumber(?string $taxNumber): static
    {
        $this->taxNumber = $taxNumber;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getType(): ?int
    {
        return $this->type;
    }

    /**
     * @param int|null $type
     * @return BillingData
     */
    public function setType(?int $type): static
    {
        $this->type = $type;
        return $this;
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
     * @return BillingData
     */
    public function setNumber(?string $number): static
    {
        $this->number = $number;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     * @return BillingData
     */
    public function setName(?string $name): static
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getShortName(): ?string
    {
        return $this->shortName;
    }

    /**
     * @param string|null $shortName
     * @return BillingData
     */
    public function setShortName(?string $shortName): static
    {
        $this->shortName = $shortName;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function getIsTaxNumberValid(): ?bool
    {
        return $this->isTaxNumberValid;
    }

    /**
     * @param bool|null $isTaxNumberValid
     * @return BillingData
     */
    public function setIsTaxNumberValid(?bool $isTaxNumberValid): static
    {
        $this->isTaxNumberValid = $isTaxNumberValid;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function getIsVatUEActivePayer(): ?bool
    {
        return $this->isVatUEActivePayer;
    }

    /**
     * @param bool|null $isVatUEActivePayer
     * @return BillingData
     */
    public function setIsVatUEActivePayer(?bool $isVatUEActivePayer): static
    {
        $this->isVatUEActivePayer = $isVatUEActivePayer;
        return $this;
    }
}
