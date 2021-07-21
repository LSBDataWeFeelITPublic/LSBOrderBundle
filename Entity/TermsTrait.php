<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Trait TermsTrait
 * @package LSB\OrderBundle\Entity
 */
trait TermsTrait
{
    /**
     * @var TermsData
     * @ORM\Embedded(class="LSB\OrderBundle\Entity\TermsData", columnPrefix="general_terms_")
     */
    protected TermsData $generalTerms;

    /**
     * @var TermsData
     * @ORM\Embedded(class="LSB\OrderBundle\Entity\TermsData", columnPrefix="privacy_policy_terms_")
     */
    protected TermsData $privacyPolicyTerms;

    /**
     * @var TermsData
     * @ORM\Embedded(class="LSB\OrderBundle\Entity\TermsData", columnPrefix="personal_data_processing_terms_")
     */
    protected TermsData $personaleDataProcessingTerms;

    /**
     * @var TermsData
     * @ORM\Embedded(class="LSB\OrderBundle\Entity\TermsData", columnPrefix="email_invoice_terms_")
     */
    protected TermsData $emailInvoiceTerms;

    /**
     * TermsTrait constructor.
     */
    public function termsConstruct()
    {
        $this->generalTerms = new TermsData();
        $this->privacyPolicyTerms = new TermsData();
        $this->personaleDataProcessingTerms = new TermsData();
        $this->emailInvoiceTerms = new TermsData();
    }

    /**
     * @return TermsData
     */
    public function getGeneralTerms(): TermsData
    {
        return $this->generalTerms;
    }

    /**
     * @param TermsData $generalTerms
     * @return $this
     */
    public function setGeneralTerms(TermsData $generalTerms): static
    {
        $this->generalTerms = $generalTerms;
        return $this;
    }

    /**
     * @return TermsData
     */
    public function getPrivacyPolicyTerms(): TermsData
    {
        return $this->privacyPolicyTerms;
    }

    /**
     * @param TermsData $privacyPolicyTerms
     * @return $this
     */
    public function setPrivacyPolicyTerms(TermsData $privacyPolicyTerms): static
    {
        $this->privacyPolicyTerms = $privacyPolicyTerms;
        return $this;
    }

    /**
     * @return TermsData
     */
    public function getPersonaleDataProcessingTerms(): TermsData
    {
        return $this->personaleDataProcessingTerms;
    }

    /**
     * @param TermsData $personaleDataProcessingTerms
     * @return $this
     */
    public function setPersonaleDataProcessingTerms(TermsData $personaleDataProcessingTerms): static
    {
        $this->personaleDataProcessingTerms = $personaleDataProcessingTerms;
        return $this;
    }

    /**
     * @return TermsData
     */
    public function getEmailInvoiceTerms(): TermsData
    {
        return $this->emailInvoiceTerms;
    }

    /**
     * @param TermsData $emailInvoiceTerms
     * @return $this
     */
    public function setEmailInvoiceTerms(TermsData $emailInvoiceTerms): static
    {
        $this->emailInvoiceTerms = $emailInvoiceTerms;
        return $this;
    }
}
