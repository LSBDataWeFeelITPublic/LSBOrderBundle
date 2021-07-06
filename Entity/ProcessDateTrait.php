<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * Trait ProcessDateTrait
 * @package LSB\OrderBundle\Entity
 */
trait ProcessDateTrait
{
    /**
     * @var DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected ?DateTime $verifiedAt;

    /**
     * @var DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected ?DateTime $realisationAt;

    /**
     * @var DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected ?DateTime $shippedAt;

    /**
     * @var DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected ?DateTime $preparedAt;

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
     * @return DateTime|null
     */
    public function getShippedAt(): ?DateTime
    {
        return $this->shippedAt;
    }

    /**
     * @param DateTime|null $shippedAt
     * @return $this
     */
    public function setShippedAt(?DateTime $shippedAt): static
    {
        $this->shippedAt = $shippedAt;
        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getPreparedAt(): ?DateTime
    {
        return $this->preparedAt;
    }

    /**
     * @param DateTime|null $preparedAt
     * @return $this
     */
    public function setPreparedAt(?DateTime $preparedAt): static
    {
        $this->preparedAt = $preparedAt;
        return $this;
    }
}