<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use LSB\UtilityBundle\Token\TokenGenerator;

/**
 * Trait ReportCodeTrait
 * @package LSB\OrderBundle\Entity
 */
trait ReportCodeTrait
{
    /**
     * @var string|null
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    protected ?string $reportCode = null;

    /**
     * Generuje prosty kod raportowania
     *
     * @param bool $force
     * @return void
     */
    public function generateReportCode(bool $force = false): void
    {
        if (!$this->reportCode || $force) {
            $this->reportCode = TokenGenerator::generateReportCode(10);
        }
    }

    /**
     * @return string|null
     */
    public function getReportCode(): ?string
    {
        return $this->reportCode;
    }

    /**
     * @param string|null $reportCode
     * @return $this
     */
    public function setReportCode(?string $reportCode): static
    {
        $this->reportCode = $reportCode;
        return $this;
    }
}
