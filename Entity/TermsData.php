<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Embeddable()
 */
class TermsData
{
    /**
     * @var bool
     * @ORM\Column(type="boolean", options={"default": false})
     */
    protected bool $isAccepted = false;

    /**
     * @var DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected ?DateTime $acceptedAt;

    /**
     * @return bool
     */
    public function isAccepted(): bool
    {
        return $this->isAccepted;
    }

    /**
     * @param bool $isAccepted
     * @return TermsData
     */
    public function setIsAccepted(bool $isAccepted): TermsData
    {
        $this->isAccepted = $isAccepted;
        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getAcceptedAt(): ?DateTime
    {
        return $this->acceptedAt;
    }

    /**
     * @param DateTime|null $acceptedAt
     * @return TermsData
     */
    public function setAcceptedAt(?DateTime $acceptedAt): TermsData
    {
        $this->acceptedAt = $acceptedAt;
        return $this;
    }
}
