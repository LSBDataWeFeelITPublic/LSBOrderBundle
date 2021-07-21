<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Trait AddressTrait
 * @package LSB\OrderBundle\Entity
 */
trait AddressTrait
{
    /**
     * @var BillingData
     * @ORM\Embedded(class="LSB\OrderBundle\Entity\BillingData", columnPrefix="billing_contractor_data_")
     */
    protected BillingData $billingContractorData;

    /**
     * @var BillingData
     * @ORM\Embedded(class="LSB\OrderBundle\Entity\BillingData", columnPrefix="recipient_contractor_data_")
     */
    protected BillingData $recipientContractorData;

    /**
     * @var Address
     * @ORM\Embedded(class="LSB\OrderBundle\Entity\Address", columnPrefix="contact_person_address_")
     */
    protected Address $contactPersonAddress;

    /**
     * @var Address
     * @ORM\Embedded(class="LSB\OrderBundle\Entity\Address", columnPrefix="invoice_delivery_address_")
     */
    protected Address $invoiceDeliveryAddress;

    /**
     *
     */
    public function addressConstruct(): void
    {
        $this->billingContractorData = new BillingData();
        $this->recipientContractorData = new BillingData();
        $this->contactPersonAddress = new BillingData();
        $this->invoiceDeliveryAddress = new BillingData();
    }

    /**
     * @return BillingData
     */
    public function getBillingContractorData(): BillingData
    {
        return $this->billingContractorData;
    }

    /**
     * @param BillingData $billingContractorData
     * @return $this
     */
    public function setBillingContractorData(BillingData $billingContractorData): static
    {
        $this->billingContractorData = $billingContractorData;
        return $this;
    }

    /**
     * @return BillingData
     */
    public function getRecipientContractorData(): BillingData
    {
        return $this->recipientContractorData;
    }

    /**
     * @param BillingData $recipientContractorData
     * @return $this
     */
    public function setRecipientContractorData(BillingData $recipientContractorData): static
    {
        $this->recipientContractorData = $recipientContractorData;
        return $this;
    }

    /**
     * @return Address
     */
    public function getContactPersonAddress(): BillingData|Address
    {
        return $this->contactPersonAddress;
    }

    /**
     * @param Address $contactPersonAddress
     * @return $this
     */
    public function setContactPersonAddress(BillingData|Address $contactPersonAddress): static
    {
        $this->contactPersonAddress = $contactPersonAddress;
        return $this;
    }

    /**
     * @return Address
     */
    public function getInvoiceDeliveryAddress(): BillingData|Address
    {
        return $this->invoiceDeliveryAddress;
    }

    /**
     * @param Address $invoiceDeliveryAddress
     * @return $this
     */
    public function setInvoiceDeliveryAddress(BillingData|Address $invoiceDeliveryAddress): static
    {
        $this->invoiceDeliveryAddress = $invoiceDeliveryAddress;
        return $this;
    }
}
