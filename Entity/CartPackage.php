<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\MappedSuperclass;
use LSB\OrderBundle\Entity\CartInterface;
use LSB\OrderBundle\Entity\CartPackageInterface;
use LSB\OrderBundle\Entity\CartPackageItem;
use LSB\OrderBundle\Entity\CartPackageItemInterface;
use LSB\OrderBundle\Entity\Package;
use LSB\OrderBundle\Model\CartCalculatorResult;
use LSB\ProductBundle\Entity\Product;

/**
 * Class CartPackage
 * @package LSB\CartBundle\Entity
 * @MappedSuperclass
 */
abstract class CartPackage extends Package implements CartPackageInterface
{

    /**
     * @var CartInterface|null
     *
     * @ORM\ManyToOne(targetEntity="LSB\OrderBundle\Entity\CartInterface", inversedBy="packages")
     */
    protected ?CartInterface $cart = null;

    /**
     * @var int|null
     * @ORM\Column(type="integer", nullable=true)
     */
    protected ?int $maxShippingDays;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false, options={"default": false})
     */
    protected bool $isMerged = false;

    /**
     * @var ArrayCollection|Collection|CartPackageItemInterface
     * @ORM\OneToMany(targetEntity="LSB\OrderBundle\Entity\CartPackageItemInterface", mappedBy="cartPackage", orphanRemoval=true, cascade={"persist", "remove"})
     * @ORM\OrderBy({"position" = "ASC", "id" = "ASC"})
     */
    protected Collection $cartPackageItems;

    /**
     * @var array
     */
    protected array $availableShippingFormsCalculations = [];

    /**
     * @var array
     */
    protected array $availableShippingForms = [];

    /**
     * @var CartCalculatorResult|null
     */
    protected ?CartCalculatorResult $cartCalculatorResult;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->cartPackageItems = new ArrayCollection();
    }

    /**
     * @throws \Exception
     */
    public function __clone()
    {
        $this->id = null;
        $this->uuid = $this->generateUuid(true);

        $this->setCreatedAt(new \DateTime());
        $this->setUpdatedAt(new \DateTime());

        $packageItemsCloned = new ArrayCollection();

        foreach ($this->getCartPackageItems() as $item) {
            $itemClone = clone $item;
            /* @var $itemClone CartPackageItem */
            $itemClone->setCartPackage($this);
            $packageItemsCloned->add($itemClone);
        }

        $this->cartPackageItems = $packageItemsCloned;
    }

    /**
     * @param Product $product
     * @param string|null $orderCode
     * @return Product|null
     */
    public function checkForExistingProduct(
        Product $product,
        ?string $orderCode = null
    ): ?CartPackageItemInterface {
        /**
         * @var CartPackageItemInterface $cartPackageItem
         */
        foreach ($this->getCartPackageItems() as $cartPackageItem) {
            if ($cartPackageItem->getProduct() === $product && $cartPackageItem->getOrderCode() == $orderCode) {
                return $cartPackageItem;
            }
        }

        return null;
    }

    /**
     * @return int|null
     */
    public function getMaxShippingDays(): ?int
    {
        return $this->maxShippingDays;
    }

    /**
     * @param int|null $maxShippingDays
     * @return CartPackage
     */
    public function setMaxShippingDays(?int $maxShippingDays): CartPackage
    {
        $this->maxShippingDays = $maxShippingDays;
        return $this;
    }

    /**
     * @return bool
     */
    public function isMerged(): bool
    {
        return $this->isMerged;
    }

    /**
     * @param bool $isMerged
     * @return CartPackage
     */
    public function setIsMerged(bool $isMerged): CartPackage
    {
        $this->isMerged = $isMerged;
        return $this;
    }

    /**
     * @return ArrayCollection|Collection|CartPackageItemInterface
     */
    public function getCartPackageItems(): ArrayCollection|Collection|CartPackageItemInterface
    {
        return $this->cartPackageItems;
    }

    /**
     * @param ${ENTRY_HINT} $cartPackageItem
     *
     * @return CartPackage
     */
    public function addCartPackageItem($cartPackageItem): CartPackage
    {
        if (false === $this->cartPackageItems->contains($cartPackageItem)) {
            $this->cartPackageItems->add($cartPackageItem);
        }
        return $this;
    }

    /**
     * @param ${ENTRY_HINT} $cartPackageItem
     *
     * @return CartPackage
     */
    public function removeCartPackageItem($cartPackageItem): CartPackage
    {
        if (true === $this->cartPackageItems->contains($cartPackageItem)) {
            $this->cartPackageItems->removeElement($cartPackageItem);
        }
        return $this;
    }

    /**
     * @param ArrayCollection|Collection|CartPackageItemInterface $cartPackageItems
     * @return CartPackage
     */
    public function setCartPackageItems(ArrayCollection|Collection|CartPackageItemInterface $cartPackageItems): CartPackage
    {
        $this->cartPackageItems = $cartPackageItems;
        return $this;
    }

    /**
     * @return array
     */
    public function getAvailableShippingFormsCalculations(): array
    {
        return $this->availableShippingFormsCalculations;
    }

    /**
     * @param ${ENTRY_HINT} $availableShippingFormsCalculation
     *
     * @return CartPackage
     */
    public function addAvailableShippingFormsCalculation($availableShippingFormsCalculation): CartPackage
    {
        if (false === in_array($availableShippingFormsCalculation, $this->availableShippingFormsCalculations, true)) {
            $this->availableShippingFormsCalculations[] = $availableShippingFormsCalculation;
        }
        return $this;
    }

    /**
     * @param ${ENTRY_HINT} $availableShippingFormsCalculation
     *
     * @return CartPackage
     */
    public function removeAvailableShippingFormsCalculation($availableShippingFormsCalculation): CartPackage
    {
        if (true === in_array($availableShippingFormsCalculation, $this->availableShippingFormsCalculations, true)) {
            $index = array_search($availableShippingFormsCalculation, $this->availableShippingFormsCalculations);
            array_splice($this->availableShippingFormsCalculations, $index, 1);
        }
        return $this;
    }

    /**
     * @param array $availableShippingFormsCalculations
     * @return CartPackage
     */
    public function setAvailableShippingFormsCalculations(array $availableShippingFormsCalculations): CartPackage
    {
        $this->availableShippingFormsCalculations = $availableShippingFormsCalculations;
        return $this;
    }

    /**
     * @return array
     */
    public function getAvailableShippingForms(): array
    {
        return $this->availableShippingForms;
    }

    /**
     * @param ${ENTRY_HINT} $availableShippingForm
     *
     * @return CartPackage
     */
    public function addAvailableShippingForm($availableShippingForm): CartPackage
    {
        if (false === in_array($availableShippingForm, $this->availableShippingForms, true)) {
            $this->availableShippingForms[] = $availableShippingForm;
        }
        return $this;
    }

    /**
     * @param ${ENTRY_HINT} $availableShippingForm
     *
     * @return CartPackage
     */
    public function removeAvailableShippingForm($availableShippingForm): CartPackage
    {
        if (true === in_array($availableShippingForm, $this->availableShippingForms, true)) {
            $index = array_search($availableShippingForm, $this->availableShippingForms);
            array_splice($this->availableShippingForms, $index, 1);
        }
        return $this;
    }

    /**
     * @param array $availableShippingForms
     * @return CartPackage
     */
    public function setAvailableShippingForms(array $availableShippingForms): CartPackage
    {
        $this->availableShippingForms = $availableShippingForms;
        return $this;
    }

    /**
     * @return CartCalculatorResult|null
     */
    public function getCartCalculatorResult(): ?CartCalculatorResult
    {
        return $this->cartCalculatorResult;
    }

    /**
     * @param CartCalculatorResult|null $cartCalculatorResult
     * @return CartPackage
     */
    public function setCartCalculatorResult(?CartCalculatorResult $cartCalculatorResult): CartPackage
    {
        $this->cartCalculatorResult = $cartCalculatorResult;
        return $this;
    }

    /**
     * @return \LSB\OrderBundle\Entity\CartInterface|null
     */
    public function getCart(): ?\LSB\OrderBundle\Entity\CartInterface
    {
        return $this->cart;
    }

    /**
     * @param \LSB\OrderBundle\Entity\CartInterface|null $cart
     * @return CartPackage
     */
    public function setCart(?\LSB\OrderBundle\Entity\CartInterface $cart): CartPackage
    {
        $this->cart = $cart;
        return $this;
    }
}
