<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use LSB\ProductBundle\Entity\ProductInterface;
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
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Length(max="255")
     */
    protected ?string $productName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Length(max="255")
     */
    protected ?string $productNumber;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Length(max="255")
     */
    protected ?string $productSetName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Length(max="255")
     */
    protected ?string $productSetNumber;

    /**
     * @var integer|null
     * @ORM\Column(type="integer", nullable=true)
     */
    protected ?int $productType;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     * @Assert\Length(max="50")
     */
    protected ?string $orderCode;

    /**
     * @ORM\Column(type="decimal", precision=18, scale=2, nullable=true)
     */
    protected $quantity;

    /**
     * @ORM\Column(type="decimal", precision=18, scale=2, nullable=true)
     */
    protected $productSetQuantity;

    /**
     * @var float|null
     * @ORM\Column(type="decimal", precision=18, scale=2, nullable=true)
     */
    protected $priceNet;

    /**
     * @var float
     * @ORM\Column(type="decimal", precision=18, scale=2, nullable=true)
     */
    protected $valueNet;

    /**
     * @var float|null
     * @ORM\Column(type="decimal", precision=18, scale=2, nullable=true)
     */
    protected $priceGross;

    /**
     * @ORM\Column(type="decimal", precision=18, scale=2, nullable=true)
     */
    protected $valueGross;

    /**
     * @ORM\Column(type="decimal", precision=18, scale=2, nullable=true)
     */
    protected $catalogPriceNet;

    /**
     * @ORM\Column(type="decimal", precision=18, scale=2, nullable=true)
     */
    protected $catalogPriceGross;

    /**
     * @ORM\Column(type="decimal", precision=18, scale=2, nullable=true)
     */
    protected $discount;

    /**
     * @var ProductInterface|null
     * @ORM\ManyToOne(targetEntity="LSB\ProductBundle\Entity\ProductInterface")
     * @ORM\JoinColumn(onDelete="SET NULL", nullable=true)
     */
    protected ?ProductInterface $product;

    /**
     * @var ProductInterface|null
     * @ORM\ManyToOne(targetEntity="LSB\ProductBundle\Entity\ProductInterface")
     * @ORM\JoinColumn(onDelete="SET NULL", nullable=true)
     */
    protected ?ProductInterface $productSet;

    /**
     * @var integer
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $taxPercentage;

    /**
     * @ORM\Column(type="decimal", precision=18, scale=2, nullable=true)
     */
    protected $taxValue;

    /**
     * @ORM\Column(type="decimal", precision=18, scale=2, nullable=true)
     */
    protected $bookedQuantity;

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
}
