<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use LSB\OrderBundle\Interfaces\OrderStatusInterface;
use JMS\Serializer\Annotation\Groups;

/**
 * Trait StatusTrait
 * @package LSB\OrderBundle\Entity
 */
trait StatusTrait
{
    /**
     * Pełna lista statusów mapowanych na nazwy
     * @var array
     */
    public static array $statusList = [
        OrderStatusInterface::STATUS_CANCELED => 'Order.Status.Canceled',
        OrderStatusInterface::STATUS_OPEN => 'Order.Status.Open',
        OrderStatusInterface::STATUS_WAITING_FOR_CONFIRMATION => 'Order.Status.WaitingForConfirmation',
        OrderStatusInterface::STATUS_WAITING_FOR_VERIFICATION => 'Order.Status.WaitingForVerification',
        OrderStatusInterface::STATUS_CONFIRMED => 'Order.Status.Confirmed',
        OrderStatusInterface::STATUS_VERIFIED => 'Order.Status.Verified',
        OrderStatusInterface::STATUS_WAITING_FOR_PAYMENT => 'Product.Edi.Order.Status.WaitingForPayment',
        OrderStatusInterface::STATUS_PAID => 'Product.Edi.Order.Status.Paid',
        OrderStatusInterface::STATUS_PLACED => 'Product.Edi.Order.Status.Placed',
        OrderStatusInterface::STATUS_PROCESSING => 'Product.Edi.Order.Status.InProgress',
        OrderStatusInterface::STATUS_SHIPPING_PREPARE => 'Product.Edi.Order.Status.ShippingPrepare',
        OrderStatusInterface::STATUS_SHIPPING_PREPARED => 'Product.Edi.Order.Status.ShippingPrepared',
        OrderStatusInterface::STATUS_SHIPPED => 'Product.Edi.Order.Status.Shipped',
        OrderStatusInterface::STATUS_COMPLETED => 'Product.Edi.Order.Status.Completed',
        OrderStatusInterface::STATUS_REJECTED => 'Product.Edi.Order.Status.Rejected'
    ];
    /**
     * @var array
     */
    public static array $paymentStatusList = [
        OrderStatusInterface::PAYMENT_STATUS_OPEN => 'Order.PaymentStatus.Open',
        OrderStatusInterface::PAYMENT_STATUS_ON_DELIVERY => 'Order.PaymentStatus.OnDelivery',
        OrderStatusInterface::PAYMENT_STATUS_UNPAID => 'Order.PaymentStatus.Unpaid',
        OrderStatusInterface::PAYMENT_STATUS_PAID => 'Order.PaymentStatus.Paid',
        OrderStatusInterface::PAYMENT_STATUS_FAILED => 'Order.PaymentStatus.Failed',
        OrderStatusInterface::PAYMENT_STATUS_CANCELLED => 'Order.PaymentStatus.Cancelled',
        OrderStatusInterface::PAYMENT_STATUS_FORWARDED_TO_BRANCH => 'Order.PaymentStatus.ForwardedToBranch',
        OrderStatusInterface::PAYMENT_STATUS_COMPLAINT => 'Order.PaymentStatus.Complaint',
    ];

    /**
     * @Groups({"Default", "EDI_User", "EDI_Moderator", "SHOP_Public"})
     *
     * @var integer
     * @ORM\Column(type="integer", nullable=false)
     */
    protected int $status = OrderStatusInterface::STATUS_OPEN;

    /**
     * @Groups({"Default", "SHOP_Public"})
     *
     * @var integer|null
     * @ORM\Column(type="integer", nullable=true)
     */
    protected ?int $paymentStatus = null;

    /**
     * @var array|null
     * @ORM\Column(type="json", nullable=true)
     */
    protected ?array $state = null;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected ?int $stage = null;

    /**
     * @var string|null
     */
    protected ?string $translatedStatus;

    /**
     * @var string|null
     */
    protected ?string $translatedPaymentStatus;

    /**
     * @return string|null
     */
    public function getMappedStatus(): ?string
    {
        if (isset(self::$statusList[$this->status])) {
            return self::$statusList[$this->status];
        } else {
            return null;
        }
    }

    /**
     * @return string|null
     */
    public function getMappedPaymentStatus(): ?string
    {
        if (isset(self::$paymentStatusList[$this->paymentStatus])) {
            return self::$paymentStatusList[$this->paymentStatus];
        } else {
            return null;
        }
    }

    /**
     * @return array
     */
    public static function getStatusList(): array
    {
        return self::$statusList;
    }

    /**
     * @param array $statusList
     */
    public static function setStatusList(array $statusList): void
    {
        self::$statusList = $statusList;
    }

    /**
     * @return array
     */
    public static function getPaymentStatusList(): array
    {
        return self::$paymentStatusList;
    }

    /**
     * @param array $paymentStatusList
     */
    public static function setPaymentStatusList(array $paymentStatusList): void
    {
        self::$paymentStatusList = $paymentStatusList;
    }

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @param int $status
     * @return $this
     */
    public function setStatus(int $status): static
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getPaymentStatus(): ?int
    {
        return $this->paymentStatus;
    }

    /**
     * @param int|null $paymentStatus
     * @return $this
     */
    public function setPaymentStatus(?int $paymentStatus): static
    {
        $this->paymentStatus = $paymentStatus;
        return $this;
    }

    /**
     * @return array|null
     */
    public function getState(): ?array
    {
        return $this->state;
    }

    /**
     * @param ${ENTRY_HINT} $state
     *
     * @return $this
     */
    public function addState($state): static
    {
        if (false === in_array($state, $this->state, true)) {
            $this->state[] = $state;
        }
        return $this;
    }

    /**
     * @param ${ENTRY_HINT} $state
     *
     * @return $this
     */
    public function removeState($state): static
    {
        if (true === in_array($state, $this->state, true)) {
            $index = array_search($state, $this->state);
            array_splice($this->state, $index, 1);
        }
        return $this;
    }

    /**
     * @param array|null $state
     * @return $this
     */
    public function setState(?array $state): static
    {
        $this->state = $state;
        return $this;
    }

    /**
     * @return int
     */
    public function getStage(): ?int
    {
        return $this->stage;
    }

    /**
     * @param int $stage
     * @return $this
     */
    public function setStage(?int $stage): static
    {
        $this->stage = $stage;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getTranslatedStatus(): ?string
    {
        return $this->translatedStatus;
    }

    /**
     * @param string|null $translatedStatus
     * @return $this
     */
    public function setTranslatedStatus(?string $translatedStatus): static
    {
        $this->translatedStatus = $translatedStatus;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getTranslatedPaymentStatus(): ?string
    {
        return $this->translatedPaymentStatus;
    }

    /**
     * @param string|null $translatedPaymentStatus
     * @return $this
     */
    public function setTranslatedPaymentStatus(?string $translatedPaymentStatus): static
    {
        $this->translatedPaymentStatus = $translatedPaymentStatus;
        return $this;
    }


}
