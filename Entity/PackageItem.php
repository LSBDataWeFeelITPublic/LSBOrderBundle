<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use LSB\ProductBundle\Entity\ProductInterface;
use LSB\UtilityBundle\Helper\ValueHelper;
use LSB\UtilityBundle\Traits\CreatedUpdatedTrait;
use LSB\UtilityBundle\Traits\PositionTrait;
use LSB\UtilityBundle\Traits\UuidTrait;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping\MappedSuperclass;

/**
 * Class PackageItem
 * @package LSB\OrderBundle\Entity
 * @ORM\HasLifecycleCallbacks()
 */
abstract class PackageItem implements PackageItemInterface
{
    use UuidTrait;
    use CreatedUpdatedTrait;
    use PositionTrait;

    /**
     * @var integer|null
     * @ORM\Column(type="integer", nullable=true)
     */
    protected ?int $type = self::TYPE_DEFAULT;

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
     * @ORM\Column(type="string", length=50, nullable=true)
     * @Assert\Length(max="50")
     */
    protected ?string $orderCode = null;

    /**
     * @var string|null
     * @ORM\Column(type="decimal", precision=18, scale=2, nullable=true)
     */
    protected ?string $quantity = null;

    /**
     * @var string|null
     * @ORM\Column(type="decimal", precision=18, scale=2, nullable=true)
     */
    protected ?string $productSetQuantity = null;

    /**
     * @var string|null
     * @ORM\Column(type="decimal", precision=18, scale=2, nullable=true)
     */
    protected ?string $priceNet = null;

    /**
     * @var string|null
     * @ORM\Column(type="decimal", precision=18, scale=2, nullable=true)
     */
    protected ?string $valueNet = null;

    /**
     * @var string|null
     * @ORM\Column(type="decimal", precision=18, scale=2, nullable=true)
     */
    protected ?string $priceGross = null;

    /**
     * @var string|null
     * @ORM\Column(type="decimal", precision=18, scale=2, nullable=true)
     */
    protected ?string $valueGross = null;

    /**
     * @var string|null
     * @ORM\Column(type="decimal", precision=18, scale=2, nullable=true)
     */
    protected ?string $catalogPriceNet = null;

    /**
     * @var string|null
     * @ORM\Column(type="decimal", precision=18, scale=2, nullable=true)
     */
    protected ?string $catalogPriceGross = null;

    /**
     * @var string|null
     * @ORM\Column(type="decimal", precision=18, scale=2, nullable=true)
     */
    protected ?string $discount = null;

    /**
     * @var ProductInterface|null
     * @ORM\ManyToOne(targetEntity="LSB\ProductBundle\Entity\ProductInterface")
     * @ORM\JoinColumn(onDelete="SET NULL", nullable=true)
     */
    protected ?ProductInterface $product = null;

    /**
     * @var ProductInterface|null
     * @ORM\ManyToOne(targetEntity="LSB\ProductBundle\Entity\ProductInterface")
     * @ORM\JoinColumn(onDelete="SET NULL", nullable=true)
     */
    protected ?ProductInterface $productSet = null;

    /**
     * @var integer|null
     * @ORM\Column(type="integer", nullable=true)
     */
    protected ?int $taxPercentage = null;

    /**
     * @var string|null
     * @ORM\Column(type="decimal", precision=18, scale=2, nullable=true)
     */
    protected ?string $taxValue = null;

    /**
     * @var string|null
     * @ORM\Column(type="decimal", precision=18, scale=2, nullable=true)
     */
    protected ?string $bookedQuantity = null;

    /**
     * @var bool
     */
    protected bool $updateValues = false;

    /**
     * PackageItem constructor
     * @throws \Exception
     */
    public function __construct()
    {
        $this->generateUuid();
    }

    public function __clone()
    {
        $this->id = null;
        $this->generateUuid(true);
    }

    /**
     * @param string|null $productName
     * @return PackageItem
     */
    public function setProductName(?string $productName): PackageItem
    {
        $this->productName = $productName;
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
     * @return int|null
     */
    public function getType(): ?int
    {
        return $this->type;
    }

    /**
     * @param int|null $type
     * @return PackageItem
     */
    public function setType(?int $type): PackageItem
    {
        $this->type = $type;
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
     * @return PackageItem
     */
    public function setProductNumber(?string $productNumber): PackageItem
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
     * @return PackageItem
     */
    public function setProductSetName(?string $productSetName): PackageItem
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
     * @return PackageItem
     */
    public function setProductSetNumber(?string $productSetNumber): PackageItem
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
     * @return PackageItem
     */
    public function setProductType(?int $productType): PackageItem
    {
        $this->productType = $productType;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getOrderCode(): ?string
    {
        return $this->orderCode;
    }

    /**
     * @param string|null $orderCode
     * @return PackageItem
     */
    public function setOrderCode(?string $orderCode): PackageItem
    {
        $this->orderCode = $orderCode;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getQuantity(): ?float
    {
        return ValueHelper::toFloat($this->quantity);
    }

    /**
     * @param float|string|null $quantity
     * @return PackageItem
     */
    public function setQuantity(float|string|null $quantity): PackageItem
    {
        $this->quantity = ValueHelper::toString($quantity);
        return $this;
    }

    /**
     * @return float|null
     */
    public function getProductSetQuantity(): ?float
    {
        return ValueHelper::toFloat($this->productSetQuantity);
    }

    /**
     * @param float|string|null $productSetQuantity
     * @return PackageItem
     */
    public function setProductSetQuantity(float|string|null $productSetQuantity): PackageItem
    {
        $this->productSetQuantity = ValueHelper::toString($productSetQuantity);
        return $this;
    }

    /**
     * @return float|null
     */
    public function getPriceNet(): ?float
    {
        return ValueHelper::toFloat($this->priceNet);
    }

    /**
     * @param float|string|null $priceNet
     * @return PackageItem
     */
    public function setPriceNet(float|string|null $priceNet): PackageItem
    {
        $this->priceNet = ValueHelper::toString($priceNet);
        return $this;
    }

    /**
     * @return float|null
     */
    public function getValueNet(): ?float
    {
        return ValueHelper::toFloat($this->valueNet);
    }

    /**
     * @param float|string|null $valueNet
     * @return PackageItem
     */
    public function setValueNet(float|string|null $valueNet): PackageItem
    {
        $this->valueNet = ValueHelper::toString($valueNet);
        return $this;
    }

    /**
     * @return float|null
     */
    public function getPriceGross(): ?float
    {
        return ValueHelper::toFloat($this->priceGross);
    }

    /**
     * @param float|string|null $priceGross
     * @return PackageItem
     */
    public function setPriceGross(float|string|null $priceGross): PackageItem
    {
        $this->priceGross = ValueHelper::toString($priceGross);
        return $this;
    }

    /**
     * @return float|null
     */
    public function getValueGross(): ?float
    {
        return ValueHelper::toFloat($this->valueGross);
    }

    /**
     * @param float|string|null $valueGross
     * @return PackageItem
     */
    public function setValueGross(float|string|null $valueGross): PackageItem
    {
        $this->valueGross = ValueHelper::toString($valueGross);
        return $this;
    }

    /**
     * @return float|null
     */
    public function getCatalogPriceNet(): ?float
    {
        return ValueHelper::toFloat($this->catalogPriceNet);
    }

    /**
     * @param float|string|null $catalogPriceNet
     * @return PackageItem
     */
    public function setCatalogPriceNet(float|string|null $catalogPriceNet): PackageItem
    {
        $this->catalogPriceNet = ValueHelper::toString($catalogPriceNet);
        return $this;
    }

    /**
     * @return float|null
     */
    public function getCatalogPriceGross(): ?float
    {
        return ValueHelper::toFloat($this->catalogPriceGross);
    }

    /**
     * @param float|string|null $catalogPriceGross
     * @return PackageItem
     */
    public function setCatalogPriceGross(float|string|null $catalogPriceGross): PackageItem
    {
        $this->catalogPriceGross = ValueHelper::toString($catalogPriceGross);
        return $this;
    }

    /**
     * @return float|null
     */
    public function getDiscount(): ?float
    {
        return ValueHelper::toFloat($this->discount);
    }

    /**
     * @param float|string|null $discount
     * @return PackageItem
     */
    public function setDiscount(float|string|null $discount): PackageItem
    {
        $this->discount = $discount;
        return $this;
    }

    /**
     * @return ProductInterface|null
     */
    public function getProduct(): ?ProductInterface
    {
        return $this->product;
    }

    /**
     * @param ProductInterface|null $product
     * @return PackageItem
     */
    public function setProduct(?ProductInterface $product): PackageItem
    {
        $this->product = $product;
        return $this;
    }

    /**
     * @return ProductInterface|null
     */
    public function getProductSet(): ?ProductInterface
    {
        return $this->productSet;
    }

    /**
     * @param ProductInterface|null $productSet
     * @return PackageItem
     */
    public function setProductSet(?ProductInterface $productSet): PackageItem
    {
        $this->productSet = $productSet;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getTaxPercentage(): ?int
    {
        return $this->taxPercentage;
    }

    /**
     * @param int|null $taxPercentage
     * @return PackageItem
     */
    public function setTaxPercentage(?int $taxPercentage): PackageItem
    {
        $this->taxPercentage = $taxPercentage;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getTaxValue(): ?float
    {
        return ValueHelper::toFloat($this->taxValue);
    }

    /**
     * @param float|string|null $taxValue
     * @return PackageItem
     */
    public function setTaxValue(float|string|null $taxValue): PackageItem
    {
        $this->taxValue = ValueHelper::toString($taxValue);
        return $this;
    }

    /**
     * @return float|null
     */
    public function getBookedQuantity(): ?float
    {
        return ValueHelper::toFloat($this->bookedQuantity);
    }

    /**
     * @param float|string|null $bookedQuantity
     * @return PackageItem
     */
    public function setBookedQuantity(float|string|null $bookedQuantity): PackageItem
    {
        $this->bookedQuantity = ValueHelper::toString($bookedQuantity);
        return $this;
    }

    /**
     * @return bool
     */
    public function isUpdateValues(): bool
    {
        return $this->updateValues;
    }

    /**
     * @param bool $updateValues
     * @return PackageItem
     */
    public function setUpdateValues(bool $updateValues): PackageItem
    {
        $this->updateValues = $updateValues;
        return $this;
    }
}
