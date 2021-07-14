<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Embeddable()
 */
class Address
{
    /**
     * @var string|null
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Length(max=255)
     */
    protected ?string $addressName = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Length(max=255)
     */
    protected ?string $street = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=50, nullable=true)
     * @Assert\Length(max=50)
     */
    protected ?string $houseNumber = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Length(max=255)
     */
    protected ?string $city = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=30, nullable=true)
     * @Assert\Length(max=30)
     */
    protected ?string $zipCode = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=50, nullable=true)
     * @Assert\Length(max=50)
     */
    protected ?string $phone = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Length(max=255)
     */
    protected ?string $email = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Length(max=255, groups={"Default"})
     */
    protected ?string $countryName = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=5, nullable=true)
     * @Assert\Length(max="5")
     */
    protected ?string $countryIsoCode = null;

    /**
     * @return string|null
     */
    public function getAddressName(): ?string
    {
        return $this->addressName;
    }

    /**
     * @param string|null $addressName
     * @return Address
     */
    public function setAddressName(?string $addressName): static
    {
        $this->addressName = $addressName;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getStreet(): ?string
    {
        return $this->street;
    }

    /**
     * @param string|null $street
     * @return Address
     */
    public function setStreet(?string $street): static
    {
        $this->street = $street;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getHouseNumber(): ?string
    {
        return $this->houseNumber;
    }

    /**
     * @param string|null $houseNumber
     * @return Address
     */
    public function setHouseNumber(?string $houseNumber): static
    {
        $this->houseNumber = $houseNumber;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCity(): ?string
    {
        return $this->city;
    }

    /**
     * @param string|null $city
     * @return Address
     */
    public function setCity(?string $city): static
    {
        $this->city = $city;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getZipCode(): ?string
    {
        return $this->zipCode;
    }

    /**
     * @param string|null $zipCode
     * @return Address
     */
    public function setZipCode(?string $zipCode): static
    {
        $this->zipCode = $zipCode;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getPhone(): ?string
    {
        return $this->phone;
    }

    /**
     * @param string|null $phone
     * @return Address
     */
    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string|null $email
     * @return Address
     */
    public function setEmail(?string $email): static
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCountryName(): ?string
    {
        return $this->countryName;
    }

    /**
     * @param string|null $countryName
     * @return Address
     */
    public function setCountryName(?string $countryName): static
    {
        $this->countryName = $countryName;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCountryIsoCode(): ?string
    {
        return $this->countryIsoCode;
    }

    /**
     * @param string|null $countryIsoCode
     * @return Address
     */
    public function setCountryIsoCode(?string $countryIsoCode): static
    {
        $this->countryIsoCode = $countryIsoCode;
        return $this;
    }
}
