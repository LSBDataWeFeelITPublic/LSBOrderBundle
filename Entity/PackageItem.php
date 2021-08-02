<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use LSB\ProductBundle\Entity\ProductInterface;
use LSB\UtilityBundle\Helper\ValueHelper;
use LSB\UtilityBundle\Traits\CreatedUpdatedTrait;
use LSB\UtilityBundle\Traits\PositionTrait;
use LSB\UtilityBundle\Traits\UuidTrait;
use LSB\UtilityBundle\Value\Value;
use Money\Money;
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
    use ItemValueTrait;

    /**
     * @var integer|null
     * @ORM\Column(type="integer", nullable=true)
     */
    protected ?int $type = self::TYPE_DEFAULT;

    /**
     * @var int|null
     * @ORM\Column(type="integer", nullable=true)
     */
    protected ?int $catalogPriceNet = null;

    /**
     * @var int|null
     * @ORM\Column(type="integer", nullable=true)
     */
    protected ?int $catalogPriceGross = null;

    /**
     * @var int|null
     * @ORM\Column(type="integer", nullable=true)
     */
    protected ?int $discount = null;

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
     * @var int|null
     * @ORM\Column(type="integer", nullable=true)
     */
    protected ?int $bookedQuantity = null;

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

    /**
     * @throws \Exception
     */
    public function __clone()
    {
        $this->id = null;
        $this->generateUuid(true);
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
     * @return $this
     */
    public function setType(?int $type): static
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @param bool $useMoney
     * @return Money|int|null
     */
    public function getCatalogPriceNet(bool $useMoney = false): Money|int|null
    {
        return $useMoney ? ValueHelper::intToMoney($this->catalogPriceNet, $this->currencyIsoCode) : $this->catalogPriceNet;
    }

    /**
     * @param Money|int|null $catalogPriceNet
     * @return $this
     */
    public function setCatalogPriceNet(Money|int|null $catalogPriceNet): static
    {
        if ($catalogPriceNet instanceof Money) {
            [$amount, $currency] = ValueHelper::moneyToIntCurrency($catalogPriceNet);
            $this->catalogPriceNet = $amount;
            $this->currencyIsoCode = $currency;
            return $this;
        }

        $this->catalogPriceNet = $catalogPriceNet;
        return $this;
    }

    /**
     * @param bool $useMoney
     * @return Money|int|null
     */
    public function getCatalogPriceGross(bool $useMoney = false): Money|int|null
    {
        return $useMoney ? ValueHelper::intToMoney($this->catalogPriceGross, $this->currencyIsoCode) : $this->catalogPriceGross;
    }

    /**
     * @param Money|int|null $catalogPriceGross
     * @return $this
     */
    public function setCatalogPriceGross(Money|int|null $catalogPriceGross): static
    {
        if ($catalogPriceGross instanceof Money) {
            [$amount, $currency] = ValueHelper::moneyToIntCurrency($catalogPriceGross);
            $this->catalogPriceGross = $amount;
            $this->currencyIsoCode = $currency;
            return $this;
        }

        $this->catalogPriceGross = $catalogPriceGross;
        return $this;
    }

    /**
     * @param bool $useValue
     * @return Value|int|null
     */
    public function getDiscount(bool $useValue = false): Value|int|null
    {
        return $useValue ? ValueHelper::intToValue($this->discount, Value::UNIT_PERCENTAGE) : $this->discount;
    }

    /**
     * @param Value|int|null $discount
     * @return $this
     */
    public function setDiscount(Value|int|null $discount): static
    {
        if ($discount instanceof Value)
        {
            [$amount, $unit] = ValueHelper::valueToIntUnit($discount);
            $this->discount = $amount;
            $this->unit = $unit;
            return $this;
        }

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
     * @return $this
     */
    public function setProduct(?ProductInterface $product): static
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
     * @return $this
     */
    public function setProductSet(?ProductInterface $productSet): static
    {
        $this->productSet = $productSet;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getBookedQuantity(): ?int
    {
        return $this->bookedQuantity;
    }

    /**
     * @param int|null $bookedQuantity
     * @return $this
     */
    public function setBookedQuantity(?int $bookedQuantity): static
    {
        $this->bookedQuantity = $bookedQuantity;
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
     * @return $this
     */
    public function setUpdateValues(bool $updateValues): static
    {
        $this->updateValues = $updateValues;
        return $this;
    }

    /**
     * TODO, remove from entity
     * @return $this
     */
    public function recalculateDiscount()
    {
        $discount = 0.00;
        $catalogPrice = $this->getCatalogPriceNet();
        $price = $this->getPriceNet();

        if ($catalogPrice && $price && $catalogPrice != 0) {
            $discount = ((($price - $catalogPrice) / $catalogPrice) * 100) * -1;
        }

        $this->setDiscount(ValueHelper::convertToValue($discount));

        return $this;
    }
}
