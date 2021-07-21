<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use LSB\UtilityBundle\Calculation\CalculationTypeTrait;
use LSB\UtilityBundle\Helper\ValueHelper;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Trait ItemValueTrait
 * @package LSB\OrderBundle\Entity
 */
trait ItemValueTrait
{
    /**
     * @var string|null
     * @ORM\Column(type="decimal", precision=18, scale=2, nullable=true, options={"default": 0})
     * @Assert\Type(type="numeric")
     */
    protected ?string $priceNet = "0";

    /**
     * @var string|null
     * @ORM\Column(type="decimal", precision=18, scale=2, nullable=true, options={"default": 0})
     * @Assert\Type(type="numeric")
     */
    protected ?string $priceGross = "0";

    /**
     * @var string|null
     * @ORM\Column(type="decimal", precision=18, scale=2, nullable=true, options={"default": 0})
     * @Assert\Type(type="numeric")
     */
    protected ?string $valueNet = "0";

    /**
     * @var string|null
     * @ORM\Column(type="decimal", precision=18, scale=2, nullable=true, options={"default": 0})
     * @Assert\Type(type="numeric")
     */
    protected ?string $valueGross = "0";

    /**
     * @var integer|null
     * @ORM\Column(type="integer", nullable=true)
     */
    protected ?int $taxRate;

    /**
     * @var string|null
     * @ORM\Column(type="decimal", precision=18, scale=2, nullable=true)
     */
    protected ?string $taxValue = null;

    /**
     * @return float|null
     */
    public function getPriceNet(): ?float
    {
        return ValueHelper::toFloat($this->priceNet);
    }

    /**
     * @param string|null $priceNet
     * @return $this
     */
    public function setPriceNet(float|string|null $priceNet): static
    {
        $this->priceNet = ValueHelper::toString($priceNet);
        return $this;
    }

    /**
     * @return float|null
     */
    public function getPriceGross(): ?float
    {
        return ValueHelper::toFloat($this->priceGross);
    }

    /**
     * @param float|string|null $priceGross
     * @return $this
     */
    public function setPriceGross(float|string|null $priceGross): static
    {
        $this->priceGross = ValueHelper::toString($priceGross);
        return $this;
    }

    /**
     * @return float|null
     */
    public function getValueNet(): ?float
    {
        return ValueHelper::toFloat($this->valueNet);
    }

    /**
     * @param float|string|null $valueNet
     * @return $this
     */
    public function setValueNet(float|string|null $valueNet): static
    {
        $this->valueNet = ValueHelper::toString($valueNet);
        return $this;
    }

    /**
     * @return float|null
     */
    public function getValueGross(): ?float
    {
        return ValueHelper::toFloat($this->valueGross);
    }

    /**
     * @param float|string|null $valueGross
     * @return $this
     */
    public function setValueGross(float|string|null $valueGross): static
    {
        $this->valueGross = ValueHelper::toString($valueGross);
        return $this;
    }

    /**
     * @return int|null
     */
    public function getTaxRate(): ?int
    {
        return $this->taxRate;
    }

    /**
     * @param int|null $taxRate
     * @return $this
     */
    public function setTaxRate(?int $taxRate): static
    {
        $this->taxRate = $taxRate;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getTaxValue(): ?string
    {
        return $this->taxValue;
    }

    /**
     * @param float|string|null $taxValue
     * @return $this
     */
    public function setTaxValue(float|string|null $taxValue): static
    {
        $this->taxValue = ValueHelper::toString($taxValue);
        return $this;
    }
}