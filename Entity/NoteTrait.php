<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Trait NoteTrait
 * @package LSB\OrderBundle\Entity
 */
trait NoteTrait
{
    /**
     * @var string|null
     * @ORM\Column(type="text", nullable=true);
     */
    protected ?string $note = null;

    /**
     * @var string|null
     * @ORM\Column(type="text", nullable=true)
     */
    protected ?string $orderVerificationNote = null;

    /**
     * @var string|null
     * @ORM\Column(type="text", nullable=true)
     */
    protected ?string $invoiceNote = null;

    /**
     * @return string|null
     */
    public function getNote(): ?string
    {
        return $this->note;
    }

    /**
     * @param string|null $note
     * @return NoteTrait
     */
    public function setNote(?string $note): static
    {
        $this->note = $note;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getOrderVerificationNote(): ?string
    {
        return $this->orderVerificationNote;
    }

    /**
     * @param string|null $orderVerificationNote
     * @return NoteTrait
     */
    public function setOrderVerificationNote(?string $orderVerificationNote): static
    {
        $this->orderVerificationNote = $orderVerificationNote;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getInvoiceNote(): ?string
    {
        return $this->invoiceNote;
    }

    /**
     * @param string|null $invoiceNote
     * @return NoteTrait
     */
    public function setInvoiceNote(?string $invoiceNote): static
    {
        $this->invoiceNote = $invoiceNote;
        return $this;
    }
}