<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use LSB\UtilityBundle\Helper\ValueHelper;

/**
 * Trait WeightTrait
 * @package LSB\OrderBundle\Entity
 */
trait WeightTrait
{
    /**
     * @var string|null
     * @ORM\Column(type="decimal", precision=18, scale=2, nullable=true)
     */
    protected ?string $weightNet;

    /**
     * @var string|null
     * @ORM\Column(type="decimal", precision=18, scale=2, nullable=true)
     */
    protected ?string $weightGross;

    /**
     * @return float|null
     */
    public function getWeightNet(): ?float
    {
        return ValueHelper::toFloat($this->weightNet);
    }

    /**
     * @param float|string|null $weightNet
     * @return $this
     */
    public function setWeightNet(float|string|null $weightNet): static
    {
        $this->weightNet = ValueHelper::toString($weightNet);
        return $this;
    }

    /**
     * @return float|null
     */
    public function getWeightGross(): ?float
    {
        return ValueHelper::toFloat($this->weightGross);
    }

    /**
     * @param float|string|null $weightGross
     * @return $this
     */
    public function setWeightGross(float|string|null $weightGross): static
    {
        $this->weightGross = ValueHelper::toString($weightGross);
        return $this;
    }
}