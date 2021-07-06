<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Interfaces;

use DateTime;

/**
 * Interface ProcessDateInterface
 * @package LSB\OrderBundle\Interfaces
 */
interface ProcessDateInterface
{
    /**
     * @return DateTime|null
     */
    public function getVerifiedAt(): ?DateTime;

    /**
     * @param DateTime|null $verifiedAt
     * @return $this
     */
    public function setVerifiedAt(?DateTime $verifiedAt): self;

    /**
     * @return DateTime|null
     */
    public function getRealisationAt(): ?DateTime;

    /**
     * @param DateTime|null $realisationAt
     * @return $this
     */
    public function setRealisationAt(?DateTime $realisationAt): self;

    /**
     * @return DateTime|null
     */
    public function getShippedAt(): ?DateTime;

    /**
     * @param DateTime|null $shippedAt
     * @return $this
     */
    public function setShippedAt(?DateTime $shippedAt): self;

    /**
     * @return DateTime|null
     */
    public function getPreparedAt(): ?DateTime;

    /**
     * @param DateTime|null $preparedAt
     * @return $this
     */
    public function setPreparedAt(?DateTime $preparedAt): self;
}