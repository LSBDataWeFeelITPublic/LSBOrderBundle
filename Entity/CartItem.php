<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Entity;

use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\MappedSuperclass;
use LSB\OrderBundle\Model\CartItemSummary;
use LSB\ProductBundle\Entity\Product;
use LSB\ProductBundle\Entity\ProductInterface;
use LSB\UtilityBundle\Traits\CreatedUpdatedTrait;
use LSB\UtilityBundle\Traits\UuidTrait;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use JMS\Serializer\Annotation\Groups;

/**
 * @UniqueEntity(
 *     fields={"product", "orderCode"},
 *     errorPath="product",
 *     message="Cart.Edi.CartItem.CartItemNotUnique"
 * )
 * @MappedSuperclass
 */
class CartItem implements CartItemInterface
{
    use UuidTrait;
    use CreatedUpdatedTrait;
    use ItemValueTrait;

    const DEFAULT_ORDER_CODE_VALUE = 'default';

    /**
     * @ORM\OneToMany(targetEntity="LSB\OrderBundle\Entity\CartPackageItemInterface", mappedBy="cartItem", cascade={"persist", "remove"}, orphanRemoval=true)
     * @ORM\OrderBy({"id" = "ASC"})
     */
    protected Collection $cartPackageItems;

    /**
     * @ORM\ManyToOne(targetEntity="LSB\OrderBundle\Entity\CartInterface", inversedBy="items")
     * @ORM\JoinColumn()
     */
    protected ?CartInterface $cart;

    /**
     * @var int|null
     * @ORM\Column(type="integer", nullable=true)
     */
    protected ?int $availability;

    /**
     * @ORM\Column(type="boolean", nullable=true, options={"default": true})
     */
    protected bool $isSelected = true;

    /**
     * @ORM\Column(type="boolean", nullable=true, options={"default": false})
     */
    protected bool $isSelectedForOption = false;

    /**
     * @var int|null
     * @ORM\Column(type="integer", nullable=true)
     */
    protected ?int $localAvailability = null;

    /**
     * @var int|null
     * @ORM\Column(type="integer", nullable=true)
     */
    protected ?int $remoteAvailability = null;

    /**
     * @Groups({"Default"})
     *
     * @var int|null
     * @ORM\Column(type="integer", nullable=true)
     */
    protected ?int $backorderAvailability = null;

    /**
     * @var ProductInterface|null
     * @ORM\ManyToOne(targetEntity="LSB\ProductBundle\Entity\ProductInterface")
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=true)
     */
    protected ?ProductInterface $product = null;

    /**
     * @var Product|null
     * @ORM\ManyToOne(targetEntity="LSB\ProductBundle\Entity\ProductInterface")
     * @ORM\JoinColumn(onDelete="SET NULL", nullable=true)
     */
    protected ?ProductInterface $productSet = null;

    /**
     * @var array
     * @ORM\Column(type="json", nullable=true)
     */
    protected array $configuration = [];

    /**
     * @var CartItemSummary
     */
    protected CartItemSummary $cartItemSummary;

    /**
     * @var CartItemSummary
     */
    protected CartItemSummary $optionSummary;

    /**
     * @var null
     */
    protected ?int $totalAvailability = null;

    /**
     * CartItem constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        $this->generateUuid();
        $this->cartItemSummary = new CartItemSummary();
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
     * @param $addQuantity
     * @return $this
     */
    public function increaseQuantity($addQuantity)
    {
        if ($this->id && $addQuantity) {
            $this->quantity += $addQuantity;
        }

        return $this;
    }

    /**
     * @param $maxQuantity
     * @return $this
     */
    public function limitQuantityToMax($maxQuantity)
    {
        if ($this->id && $maxQuantity && $maxQuantity > $this->quantity) {
            $this->quantity = $maxQuantity;
        }

        return $this;
    }

    /**
     * @return Collection
     */
    public function getCartPackageItems(): Collection
    {
        return $this->cartPackageItems;
    }

    /**
     * @param ${ENTRY_HINT} $cartPackageItem
     *
     * @return CartItem
     */
    public function addCartPackageItem($cartPackageItem): CartItem
    {
        if (false === $this->cartPackageItems->contains($cartPackageItem)) {
            $this->cartPackageItems->add($cartPackageItem);
        }
        return $this;
    }

    /**
     * @param ${ENTRY_HINT} $cartPackageItem
     *
     * @return CartItem
     */
    public function removeCartPackageItem($cartPackageItem): CartItem
    {
        if (true === $this->cartPackageItems->contains($cartPackageItem)) {
            $this->cartPackageItems->removeElement($cartPackageItem);
        }
        return $this;
    }

    /**
     * @param Collection $cartPackageItems
     * @return CartItem
     */
    public function setCartPackageItems(Collection $cartPackageItems): CartItem
    {
        $this->cartPackageItems = $cartPackageItems;
        return $this;
    }

    /**
     * @return CartInterface|null
     */
    public function getCart(): ?CartInterface
    {
        return $this->cart;
    }

    /**
     * @param CartInterface|null $cart
     * @return CartItem
     */
    public function setCart(?CartInterface $cart): CartItem
    {
        $this->cart = $cart;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getAvailability(): ?int
    {
        return $this->availability;
    }

    /**
     * @param int|null $availability
     * @return CartItem
     */
    public function setAvailability(?int $availability): CartItem
    {
        $this->availability = $availability;
        return $this;
    }

    /**
     * @return bool
     */
    public function isSelected(): bool
    {
        return $this->isSelected;
    }

    /**
     * @param bool $isSelected
     * @return CartItem
     */
    public function setIsSelected(bool $isSelected): CartItem
    {
        $this->isSelected = $isSelected;
        return $this;
    }

    /**
     * @return bool
     */
    public function isSelectedForOption(): bool
    {
        return $this->isSelectedForOption;
    }

    /**
     * @param bool $isSelectedForOption
     * @return CartItem
     */
    public function setIsSelectedForOption(bool $isSelectedForOption): CartItem
    {
        $this->isSelectedForOption = $isSelectedForOption;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getLocalAvailability(): ?int
    {
        return $this->localAvailability;
    }

    /**
     * @param int|null $localAvailability
     * @return CartItem
     */
    public function setLocalAvailability(?int $localAvailability): CartItem
    {
        $this->localAvailability = $localAvailability;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getRemoteAvailability(): ?int
    {
        return $this->remoteAvailability;
    }

    /**
     * @param int|null $remoteAvailability
     * @return CartItem
     */
    public function setRemoteAvailability(?int $remoteAvailability): CartItem
    {
        $this->remoteAvailability = $remoteAvailability;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getBackorderAvailability(): ?int
    {
        return $this->backorderAvailability;
    }

    /**
     * @param int|null $backorderAvailability
     * @return CartItem
     */
    public function setBackorderAvailability(?int $backorderAvailability): CartItem
    {
        $this->backorderAvailability = $backorderAvailability;
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
     * @return CartItem
     */
    public function setProduct(?ProductInterface $product): CartItem
    {
        $this->product = $product;
        return $this;
    }

    /**
     * @return Product|null
     */
    public function getProductSet(): Product|ProductInterface|null
    {
        return $this->productSet;
    }

    /**
     * @param Product|null $productSet
     * @return CartItem
     */
    public function setProductSet(Product|ProductInterface|null $productSet): CartItem
    {
        $this->productSet = $productSet;
        return $this;
    }

    /**
     * @return array
     */
    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    /**
     * @param ${ENTRY_HINT} $configuration
     *
     * @return CartItem
     */
    public function addConfiguration($configuration): CartItem
    {
        if (false === in_array($configuration, $this->configuration, true)) {
            $this->configuration[] = $configuration;
        }
        return $this;
    }

    /**
     * @param ${ENTRY_HINT} $configuration
     *
     * @return CartItem
     */
    public function removeConfiguration($configuration): CartItem
    {
        if (true === in_array($configuration, $this->configuration, true)) {
            $index = array_search($configuration, $this->configuration);
            array_splice($this->configuration, $index, 1);
        }
        return $this;
    }

    /**
     * @param array $configuration
     * @return CartItem
     */
    public function setConfiguration(array $configuration): CartItem
    {
        $this->configuration = $configuration;
        return $this;
    }

    /**
     * @return CartItemSummary
     */
    public function getCartItemSummary(): CartItemSummary
    {
        return $this->cartItemSummary;
    }

    /**
     * @param CartItemSummary $cartItemSummary
     * @return CartItem
     */
    public function setCartItemSummary(CartItemSummary $cartItemSummary): CartItem
    {
        $this->cartItemSummary = $cartItemSummary;
        return $this;
    }

    /**
     * @return CartItemSummary
     */
    public function getOptionSummary(): CartItemSummary
    {
        return $this->optionSummary;
    }

    /**
     * @param CartItemSummary $optionSummary
     * @return CartItem
     */
    public function setOptionSummary(CartItemSummary $optionSummary): CartItem
    {
        $this->optionSummary = $optionSummary;
        return $this;
    }

    /**
     * @return null
     */
    public function getTotalAvailability(): ?int
    {
        return $this->totalAvailability;
    }

    /**
     * @param null $totalAvailability
     * @return CartItem
     */
    public function setTotalAvailability(?int $totalAvailability): CartItem
    {
        $this->totalAvailability = $totalAvailability;
        return $this;
    }
}
