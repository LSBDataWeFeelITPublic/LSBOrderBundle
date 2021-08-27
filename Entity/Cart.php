<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Entity;

use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\MappedSuperclass;
use Exception;
use JetBrains\PhpStorm\Pure;
use LSB\CartBundle\Entity\CartItem;
use LSB\ContractorBundle\Entity\ContractorInterface;
use LSB\LocaleBundle\Entity\CountryInterface;
use LSB\LocaleBundle\Entity\CurrencyInterface;
use LSB\OrderBundle\Model\CartSummary;
use Doctrine\Common\Collections\ArrayCollection;
use LSB\PaymentBundle\Entity\MethodInterface as PaymentMethodInterface;
use LSB\UserBundle\Entity\UserInterface;
use LSB\UtilityBundle\Traits\CreatedUpdatedTrait;
use LSB\UtilityBundle\Traits\UuidTrait;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\Groups;

/**
 * @ORM\HasLifecycleCallbacks()
 * @MappedSuperclass
 */
class Cart implements CartInterface
{
    use UuidTrait;
    use CreatedUpdatedTrait;
    use TotalValueCostTrait;
    use NoteTrait;
    use TermsTrait;
    use AddressTrait;
    use ReportCodeTrait;

    /**
     * @var array|string[]
     */
    public static array $oversaleTypeList = [
        self::DELIVERY_VARIANT_WAIT_FOR_ALL => 'Cart.OversaleType.WaitForAll',
        self::DELIVERY_VARIANT_SEND_AVAILABLE => 'Cart.OversaleType.SendAvailable',
    ];

    /**
     * @var array|string[]
     */
    public static array $fullOversaleTypeList = [
        self::DELIVERY_VARIANT_ONLY_AVAILABLE => 'Cart.OversaleType.OnlyAvailable',
        self::DELIVERY_VARIANT_WAIT_FOR_ALL => 'Cart.OversaleType.WaitForAll',
        self::DELIVERY_VARIANT_SEND_AVAILABLE => 'Cart.OversaleType.SendAvailable',
        self::DELIVERY_VARIANT_WAIT_FOR_BACKORDER => 'Cart.OversaleType.WaitForBackorder',
    ];

    /**
     * @var string[]
     */
    public static array $processingTypeList = [
        self::PROCESSING_TYPE_DEFAULT => 'Cart.ProcessingType.Default',
    ];

    /**
     * @var string[]
     */
    public static array $webProcessingTypeList = [
        self::PROCESSING_TYPE_DEFAULT => 'Cart.ProcessingType.Default',
    ];

    public static array $invoiceDeliveryTypeList = [
        self::INVOICE_DELIVERY_USE_CUSTOMER_DATA => 'Cart.Label.InvoiceSendUsingCustomerData',
        self::INVOICE_DELIVERY_USE_NEW_ADDRESS => 'Cart.Label.InvoiceSendUsingNewAddress',
    ];

    /**
     * Stany walidacji kroków
     *
     * @var string[]
     */
    public static array $validatedStepList = [
        self::CART_STEP_1 => 'Cart.Step.1',
        self::CART_STEP_2 => 'Cart.Step.2',
        self::CART_STEP_3 => 'Cart.Step.3',
        self::CART_STEP_ORDER_CREATED => 'Cart.Step.OrderCreated',
        self::CART_STEP_CLOSED_BY_MERGE => 'Cart.Step.ClosedByMerge',
        self::CART_STEP_CLOSED_MANUALLY => 'Cart.Step.ClosedManually'
    ];

    /**
     * Lista typów autoryzacji użytkownika w koszyku
     *
     * @var string[]
     */
    public static array $authTypeList = [
        self::AUTH_TYPE_USER => 'Cart.AuthType.User',
        self::AUTH_TYPE_REGISTRATION => 'Cart.AuthType.Registration',
        self::AUTH_TYPE_WITHOUT_REGISTRATION => 'Cart.AuthType.WithoutRegistration'
    ];

    /**
     * @var array
     * @deprecated
     */
    public static array $customerBillingColumns = [
        'customerAddress',
        'customerHouseNumber',
        'customerCity',
        'customerZipCode',
        'customerEmail'
    ];

    /**
     * @var array
     * @deprecated
     */
    public static array $customerBillingCompanyColumns = [
        'customerTaxNumber',
        'customerName',
    ];

    /**
     * @var array
     * @deprecated
     */
    public static array $privateBillingCompanyColumns = [
        'customerFirstName',
        'customerLastName',
    ];

    /**
     * @var string|null
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Length(max=255)
     */
    protected ?string $sessionId = null;

    /**
     * @var UserInterface|null
     * @ORM\ManyToOne(targetEntity="LSB\UserBundle\Entity\UserInterface")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    protected ?UserInterface $user = null;

    /**
     * @var PaymentMethodInterface|null
     *
     * @ORM\ManyToOne(targetEntity="LSB\PaymentBundle\Entity\MethodInterface", fetch="EAGER")
     * @ORM\JoinColumn()
     */
    protected ?PaymentMethodInterface $paymentMethod = null;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="LSB\OrderBundle\Entity\CartPackageInterface", mappedBy="cart", orphanRemoval=true, cascade={"persist", "remove"})
     * @ORM\OrderBy({"packageType" = "ASC", "maxShippingDays" = "ASC"})
     */
    protected Collection $cartPackages;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="LSB\OrderBundle\Entity\CartItemInterface", mappedBy="cart", orphanRemoval=true, cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="cart_item_id", referencedColumnName="id", nullable=true)
     * @ORM\OrderBy({"id" = "ASC"})
     */
    protected Collection $cartItems;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=255, nullable=true);
     */
    protected ?string $clientOrderNumber = null;

    /**
     * @var int|null
     *
     * @Groups({"Default", "EDI_User", "SHOP_Public"})
     * @ORM\Column(type="integer", nullable=true)
     */
    protected ?int $suggestedDeliveryVariant = null;

    /**
     * @var int|null
     *
     * @Groups({"Default", "EDI_User", "SHOP_Public"})
     * @ORM\Column(type="integer", nullable=true)
     */
    protected ?int $selectedDeliveryVariant = null;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="LSB\OrderBundle\Entity\OrderInterface", mappedBy="cart");
     * @ORM\OrderBy({"id" = "ASC"})
     */
    protected Collection $orders;

    /**
     * @var CurrencyInterface|null
     * @ORM\ManyToOne(targetEntity="LSB\LocaleBundle\Entity\CurrencyInterface")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected ?CurrencyInterface $currency = null;

    /**
     * @var int|null
     *
     * @Groups({"Default", "EDI_User", "SHOP_Public"})
     * @ORM\Column(type="integer", nullable=true)
     */
    protected ?int $validatedStep = null;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", nullable=false, options={"default": false})
     */
    protected bool $isConvertedFromSession = false;

    /**
     * @var boolean|null
     * @ORM\Column(type="boolean", options={"default": false})
     */
    protected bool $isMerged = false;

    /**
     * @var int
     * @ORM\Column(type="integer", options={"default": 1})
     * @Assert\NotBlank(groups={"EdiCartProcessing"})
     */
    protected int $processingType = self::PROCESSING_TYPE_DEFAULT;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=false, options={"default": 1})
     */
    protected int $type = self::CART_TYPE_SESSION;

    /**
     * @var int|null
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\NotBlank(groups={"SHOP_CartAuth"})
     */
    protected ?int $authType = null;

    /**
     * @var DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected ?DateTime $realisationAt;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", options={"default": false})
     */
    protected bool $isLocked = false;

    /**
     * @var bool
     * @Groups({"Default", "SHOP_Public"})
     * @ORM\Column(type="boolean", nullable=false, options={"default": false})
     */
    protected bool $isOrderVerificationRequested = false;

    /**
     * @ORM\ManyToOne(targetEntity="LSB\ContractorBundle\Entity\ContractorInterface")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    protected ?ContractorInterface $recipientContractor;

    /**
     * @var CountryInterface|null
     *
     * @ORM\ManyToOne(targetEntity="LSB\LocaleBundle\Entity\CountryInterface")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    protected ?CountryInterface $recipientContractorCountry;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=false, options={"default": false})
     */
    protected bool $isProcessedForSupplier = false;

    /**
     * @var DateTime|null
     *
     * @Groups({"Default"})
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected ?DateTime $abandonmentNotificationSentAt;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=80, nullable=true)
     */
    protected ?string $abandonmentToken;

    /**
     * @var DateTime|null
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected ?DateTime $usedAsAbandonedCartAt;

    /**
     * @var ContractorInterface|null
     *
     * @ORM\ManyToOne(targetEntity="LSB\ContractorBundle\Entity\ContractorInterface")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    protected ?ContractorInterface $billingContractor = null;

    /**
     * @var CountryInterface|null
     *
     * @ORM\ManyToOne(targetEntity="LSB\LocaleBundle\Entity\CountryInterface")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    protected ?CountryInterface $billingContractorCountry = null;

    /**
     * @var ContractorInterface|null
     *
     * @ORM\ManyToOne(targetEntity="LSB\ContractorBundle\Entity\ContractorInterface")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    protected ?ContractorInterface $suggestedBillingContractor = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected ?string $transactionId;

    /**
     * @var DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected ?DateTime $transactionIdUpdatedAt;

    /**
     * @var DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected ?DateTime $transactionIdUsedAt;

    /**
     * @var CartSummary|null
     */
    protected ?CartSummary $cartSummary = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->cartPackages = new ArrayCollection();
        $this->cartItems = new ArrayCollection();
        $this->orders = new ArrayCollection();
        $this->cartSummary = new CartSummary();

        $this->addressConstruct();
        $this->termsConstruct();
    }

    /**
     * TODO refactor
     *
     * @param int $type
     * @param bool $onlySelected
     * @return array
     */
    public function getCartItemsByProductType(int $type, bool $onlySelected = false): array
    {
        $items = [];

        /**
         * @var CartItemInterface $cartItem
         */
        foreach ($this->getCartItems() as $cartItem) {
            if ($cartItem->getProduct() && $cartItem->getProduct()->getType() === $type && !$onlySelected
                || $cartItem->getProduct() && $cartItem->getProduct()->getType() === $type && $onlySelected && $cartItem->isSelected()
            ) {
                $items[$cartItem->getUuid()] = $cartItem;
            }
        }

        return $items;
    }

    /**
     * @throws Exception
     */
    public function __clone()
    {
        $this->id = null;
        $this->generateUuid(true);
    }

    /**
     * @return CartSummary
     */
    public function getCartSummary(): CartSummary
    {
        if ($this->cartSummary === null) {
            $this->cartSummary = new CartSummary();
        }

        return $this->cartSummary;
    }

    /**
     * @param CartSummary $cartSummary
     */
    public function setCartSummary(CartSummary $cartSummary)
    {
        $this->cartSummary = $cartSummary;
    }

    /**
     * @return $this
     */
    public function clearCartSummary(): static
    {
        $this->cartSummary = new CartSummary();

        return $this;
    }

    /**
     * Zamiana z foreach na criteria
     *
     * @return int
     */
    public function countSelectedItems(): int
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq("isSelected", true))
            ->orderBy(['id' => Criteria::ASC]);

        return $this->cartItems->matching($criteria)->count();
    }

    /**
     * @return bool
     */
    #[Pure] public function hasSelectedCartItem(): bool
    {
        /**
         * @var CartItemInterface $cartItem
         */
        foreach ($this->cartItems as $cartItem) {
            if ($cartItem->isSelected()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return ArrayCollection
     */
    public function getSelectedCartItems(): ArrayCollection
    {
        $selectedItems = new ArrayCollection();

        /**
         * @var CartItemInterface $item
         */
        foreach ($this->cartItems as $item) {
            if ($item->isSelected()) {
                $selectedItems->add($item);
            }
        }

        return $selectedItems;
    }

    /**
     * @return $this
     */
    public function unlockCart(): static
    {
        $this->isLocked = false;

        return $this;
    }

    /**
     * @param bool $clearCustomerData
     * @return $this
     */
    public function clearCart(bool $clearCustomerData = true)
    {
        if ($this->orders->count() === 0) {
            $this->authType = null;
            $this->cartPackages->clear();
            $this->cartItems->clear();
            $this->suggestedDeliveryVariant = self::DELIVERY_VARIANT_ONLY_AVAILABLE;
            $this->isOrderVerificationRequested = false;
            $this->paymentMethod = null;
            $this->validatedStep = null;

            if ($clearCustomerData) {
                //$this->clearCustomerData();
            }
        }

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSessionId(): ?string
    {
        return $this->sessionId;
    }

    /**
     * @param string|null $sessionId
     * @return $this
     */
    public function setSessionId(?string $sessionId): static
    {
        $this->sessionId = $sessionId;
        return $this;
    }

    /**
     * @return UserInterface|null
     */
    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    /**
     * @param UserInterface|null $user
     * @return $this
     */
    public function setUser(?UserInterface $user): static
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return PaymentMethodInterface|null
     */
    public function getPaymentMethod(): ?PaymentMethodInterface
    {
        return $this->paymentMethod;
    }

    /**
     * @param PaymentMethodInterface|null $paymentMethod
     * @return $this
     */
    public function setPaymentMethod(?PaymentMethodInterface $paymentMethod): static
    {
        $this->paymentMethod = $paymentMethod;
        return $this;
    }

    /**
     * @return Collection
     */
    public function getCartPackages(): ArrayCollection|Collection
    {
        return $this->cartPackages;
    }

    /**
     * @param ${ENTRY_HINT} $cartPackage
     *
     * @return $this
     */
    public function addCartPackage($cartPackage): static
    {
        if (false === $this->cartPackages->contains($cartPackage)) {
            $this->cartPackages->add($cartPackage);
        }
        return $this;
    }

    /**
     * @param ${ENTRY_HINT} $cartPackage
     *
     * @return $this
     */
    public function removeCartPackage($cartPackage): static
    {
        if (true === $this->cartPackages->contains($cartPackage)) {
            $this->cartPackages->removeElement($cartPackage);
        }
        return $this;
    }

    /**
     * @param Collection $cartPackages
     * @return $this
     */
    public function setCartPackages(ArrayCollection|Collection $cartPackages): static
    {
        $this->cartPackages = $cartPackages;
        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getCartItems(): ArrayCollection|Collection
    {
        return $this->cartItems;
    }

    /**
     * @param ${ENTRY_HINT} $cartItem
     *
     * @return $this
     */
    public function addCartItem($cartItem): static
    {
        if (false === $this->cartItems->contains($cartItem)) {
            $this->cartItems->add($cartItem);
        }
        return $this;
    }

    /**
     * @param ${ENTRY_HINT} $cartItem
     *
     * @return $this
     */
    public function removeCartItem($cartItem): static
    {
        if (true === $this->cartItems->contains($cartItem)) {
            $this->cartItems->removeElement($cartItem);
        }
        return $this;
    }

    /**
     * @param ArrayCollection $cartItems
     * @return $this
     */
    public function setCartItems(ArrayCollection|Collection $cartItems): static
    {
        $this->cartItems = $cartItems;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getClientOrderNumber(): ?string
    {
        return $this->clientOrderNumber;
    }

    /**
     * @param string|null $clientOrderNumber
     * @return $this
     */
    public function setClientOrderNumber(?string $clientOrderNumber): static
    {
        $this->clientOrderNumber = $clientOrderNumber;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getSuggestedDeliveryVariant(): ?int
    {
        return $this->suggestedDeliveryVariant;
    }

    /**
     * @param int|null $suggestedDeliveryVariant
     * @return $this
     */
    public function setSuggestedDeliveryVariant(?int $suggestedDeliveryVariant): static
    {
        $this->suggestedDeliveryVariant = $suggestedDeliveryVariant;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getSelectedDeliveryVariant(): ?int
    {
        return $this->selectedDeliveryVariant;
    }

    /**
     * @param int|null $selectedDeliveryVariant
     * @return $this
     */
    public function setSelectedDeliveryVariant(?int $selectedDeliveryVariant): static
    {
        $this->selectedDeliveryVariant = $selectedDeliveryVariant;
        return $this;
    }

    /**
     * @return Collection
     */
    public function getOrders(): ArrayCollection|Collection
    {
        return $this->orders;
    }

    /**
     * @param ${ENTRY_HINT} $order
     *
     * @return $this
     */
    public function addOrder($order): static
    {
        if (false === $this->orders->contains($order)) {
            $this->orders->add($order);
        }
        return $this;
    }

    /**
     * @param ${ENTRY_HINT} $order
     *
     * @return $this
     */
    public function removeOrder($order): static
    {
        if (true === $this->orders->contains($order)) {
            $this->orders->removeElement($order);
        }
        return $this;
    }

    /**
     * @param Collection $orders
     * @return $this
     */
    public function setOrders(ArrayCollection|Collection $orders): static
    {
        $this->orders = $orders;
        return $this;
    }

    /**
     * @return CurrencyInterface|null
     */
    public function getCurrency(): ?CurrencyInterface
    {
        return $this->currency;
    }

    /**
     * @param CurrencyInterface|null $currency
     * @return $this
     */
    public function setCurrency(?CurrencyInterface $currency): static
    {
        $this->currency = $currency;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getValidatedStep(): ?int
    {
        return $this->validatedStep;
    }

    /**
     * @param int|null $validatedStep
     * @return $this
     */
    public function setValidatedStep(?int $validatedStep): static
    {
        $this->validatedStep = $validatedStep;
        return $this;
    }

    /**
     * @return bool
     */
    public function isConvertedFromSession(): bool
    {
        return $this->isConvertedFromSession;
    }

    /**
     * @param bool $isConvertedFromSession
     * @return $this
     */
    public function setIsConvertedFromSession(bool $isConvertedFromSession): static
    {
        $this->isConvertedFromSession = $isConvertedFromSession;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function getIsMerged(): ?bool
    {
        return $this->isMerged;
    }

    /**
     * @param bool|null $isMerged
     * @return $this
     */
    public function setIsMerged(?bool $isMerged): static
    {
        $this->isMerged = $isMerged;
        return $this;
    }

    /**
     * @return int
     */
    public function getProcessingType(): int
    {
        return $this->processingType;
    }

    /**
     * @param int $processingType
     * @return $this
     */
    public function setProcessingType(int $processingType): static
    {
        $this->processingType = $processingType;
        return $this;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @param int $type
     * @return $this
     */
    public function setType(int $type): static
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getAuthType(): ?int
    {
        return $this->authType;
    }

    /**
     * @param int|null $authType
     * @return $this
     */
    public function setAuthType(?int $authType): static
    {
        $this->authType = $authType;
        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getRealisationAt(): ?DateTime
    {
        return $this->realisationAt;
    }

    /**
     * @param DateTime|null $realisationAt
     * @return $this
     */
    public function setRealisationAt(?DateTime $realisationAt): static
    {
        $this->realisationAt = $realisationAt;
        return $this;
    }

    /**
     * @return bool
     */
    public function isLocked(): bool
    {
        return $this->isLocked;
    }

    /**
     * @param bool $isLocked
     * @return $this
     */
    public function setIsLocked(bool $isLocked): static
    {
        $this->isLocked = $isLocked;
        return $this;
    }

    /**
     * @return bool
     */
    public function isOrderVerificationRequested(): bool
    {
        return $this->isOrderVerificationRequested;
    }

    /**
     * @param bool $isOrderVerificationRequested
     * @return $this
     */
    public function setIsOrderVerificationRequested(bool $isOrderVerificationRequested): static
    {
        $this->isOrderVerificationRequested = $isOrderVerificationRequested;
        return $this;
    }

    /**
     * @return ContractorInterface|null
     */
    public function getRecipientContractor(): ?ContractorInterface
    {
        return $this->recipientContractor;
    }

    /**
     * @param ContractorInterface|null $recipientContractor
     * @return $this
     */
    public function setRecipientContractor(?ContractorInterface $recipientContractor): static
    {
        $this->recipientContractor = $recipientContractor;
        return $this;
    }

    /**
     * @return bool
     */
    public function isProcessedForSupplier(): bool
    {
        return $this->isProcessedForSupplier;
    }

    /**
     * @param bool $isProcessedForSupplier
     * @return $this
     */
    public function setIsProcessedForSupplier(bool $isProcessedForSupplier): static
    {
        $this->isProcessedForSupplier = $isProcessedForSupplier;
        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getAbandonmentNotificationSentAt(): ?DateTime
    {
        return $this->abandonmentNotificationSentAt;
    }

    /**
     * @param DateTime|null $abandonmentNotificationSentAt
     * @return $this
     */
    public function setAbandonmentNotificationSentAt(?DateTime $abandonmentNotificationSentAt): static
    {
        $this->abandonmentNotificationSentAt = $abandonmentNotificationSentAt;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getAbandonmentToken(): ?string
    {
        return $this->abandonmentToken;
    }

    /**
     * @param string|null $abandonmentToken
     * @return $this
     */
    public function setAbandonmentToken(?string $abandonmentToken): static
    {
        $this->abandonmentToken = $abandonmentToken;
        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getUsedAsAbandonedCartAt(): ?DateTime
    {
        return $this->usedAsAbandonedCartAt;
    }

    /**
     * @param DateTime|null $usedAsAbandonedCartAt
     * @return $this
     */
    public function setUsedAsAbandonedCartAt(?DateTime $usedAsAbandonedCartAt): static
    {
        $this->usedAsAbandonedCartAt = $usedAsAbandonedCartAt;
        return $this;
    }

    /**
     * @return ContractorInterface|null
     */
    public function getBillingContractor(): ?ContractorInterface
    {
        return $this->billingContractor;
    }

    /**
     * @param ContractorInterface|null $billingContractor
     * @return $this
     */
    public function setBillingContractor(?ContractorInterface $billingContractor): static
    {
        $this->billingContractor = $billingContractor;
        return $this;
    }

    /**
     * @return ContractorInterface|null
     */
    public function getSuggestedBillingContractor(): ?ContractorInterface
    {
        return $this->suggestedBillingContractor;
    }

    /**
     * @param ContractorInterface|null $suggestedBillingContractor
     * @return $this
     */
    public function setSuggestedBillingContractor(?ContractorInterface $suggestedBillingContractor): static
    {
        $this->suggestedBillingContractor = $suggestedBillingContractor;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }

    /**
     * @param string|null $transactionId
     * @return $this
     */
    public function setTransactionId(?string $transactionId): static
    {
        $this->transactionId = $transactionId;
        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getTransactionIdUpdatedAt(): ?DateTime
    {
        return $this->transactionIdUpdatedAt;
    }

    /**
     * @param DateTime|null $transactionIdUpdatedAt
     * @return $this
     */
    public function setTransactionIdUpdatedAt(?DateTime $transactionIdUpdatedAt): static
    {
        $this->transactionIdUpdatedAt = $transactionIdUpdatedAt;
        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getTransactionIdUsedAt(): ?DateTime
    {
        return $this->transactionIdUsedAt;
    }

    /**
     * @param DateTime|null $transactionIdUsedAt
     * @return $this
     */
    public function setTransactionIdUsedAt(?DateTime $transactionIdUsedAt): static
    {
        $this->transactionIdUsedAt = $transactionIdUsedAt;
        return $this;
    }

    /**
     * @return CountryInterface|null
     */
    public function getBillingContractorCountry(): ?CountryInterface
    {
        return $this->billingContractorCountry;
    }

    /**
     * @param CountryInterface|null $billingContractorCountry
     * @return $this
     */
    public function setBillingContractorCountry(?CountryInterface $billingContractorCountry): static
    {
        $this->billingContractorCountry = $billingContractorCountry;
        return $this;
    }


}
