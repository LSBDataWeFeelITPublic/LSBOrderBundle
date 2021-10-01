<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\MappedSuperclass;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class OrderPackageItem
 * @package LSB\OrderBundle\Entity
 * @ORM\HasLifecycleCallbacks()
 * @MappedSuperclass
 */
abstract class OrderPackageItem extends PackageItem implements OrderPackageItemInterface
{
    /**
     * @var string|null
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Length(max="255")
     */
    protected ?string $productName = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Length(max="255")
     */
    protected ?string $productNumber = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Length(max="255")
     */
    protected ?string $productSetName = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Length(max="255")
     */
    protected ?string $productSetNumber = null;

    /**
     * @var integer|null
     * @ORM\Column(type="integer", nullable=true)
     */
    protected ?int $productType = self::PRODUCT_TYPE_DEFAULT;

    /**
     * @var OrderPackageInterface|null
     * @ORM\ManyToOne(targetEntity="LSB\OrderBundle\Entity\OrderPackageInterface", inversedBy="items")
     */
    protected ?OrderPackageInterface $orderPackage = null;

    /**
     * @return OrderPackageInterface|null
     */
    public function getOrderPackage(): ?OrderPackageInterface
    {
        return $this->orderPackage;
    }

    /**
     * @param OrderPackageInterface|null $orderPackage
     * @return $this
     */
    public function setOrderPackage(?OrderPackageInterface $orderPackage): self
    {
        $this->orderPackage = $orderPackage;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getProductName(): ?string
    {
        return $this->productName;
    }

    /**
     * @param string|null $productName
     * @return $this
     */
    public function setProductName(?string $productName): static
    {
        $this->productName = $productName;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getProductNumber(): ?string
    {
        return $this->productNumber;
    }

    /**
     * @param string|null $productNumber
     * @return $this
     */
    public function setProductNumber(?string $productNumber): static
    {
        $this->productNumber = $productNumber;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getProductSetName(): ?string
    {
        return $this->productSetName;
    }

    /**
     * @param string|null $productSetName
     * @return $this
     */
    public function setProductSetName(?string $productSetName): static
    {
        $this->productSetName = $productSetName;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getProductSetNumber(): ?string
    {
        return $this->productSetNumber;
    }

    /**
     * @param string|null $productSetNumber
     * @return $this
     */
    public function setProductSetNumber(?string $productSetNumber): static
    {
        $this->productSetNumber = $productSetNumber;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getProductType(): ?int
    {
        return $this->productType;
    }

    /**
     * @param int|null $productType
     * @return $this
     */
    public function setProductType(?int $productType): static
    {
        $this->productType = $productType;
        return $this;
    }
}
