<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Interfaces;

/**
 * Interface WeightInterface
 * @package LSB\OrderBundle\Interfaces
 */
interface WeightInterface
{
    /**
     * @return float|null
     */
    public function getWeightNet(): ?float;

    /**
     * @param float|string|null $weightNet
     * @return $this
     */
    public function setWeightNet(float|string|null $weightNet): self;

    /**
     * @return float|null
     */
    public function getWeightGross(): ?float;

    /**
     * @param float|string|null $weightGross
     * @return $this
     */
    public function setWeightGross(float|string|null $weightGross): self;
}