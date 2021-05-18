<?php

namespace LSB\OrderBundle\Traits;

use Doctrine\ORM\Mapping as ORM;
use LSB\OrderBundle\Interfaces\OrderStatusInterface;
use JMS\Serializer\Annotation\Groups;

/**
 * Trait StatusTrait
 * @package LSB\OrderBundle\Traits
 */
trait StatusTrait
{
    /**
     * Pełna lista statusów mapowanych na nazwy
     * @var array
     */
    public static $statusList = [
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
    public static $paymentStatusList = [
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
    protected $status = self::STATUS_OPEN;

    /**
     * @Groups({"Default", "SHOP_Public"})
     *
     * @var integer|null
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $paymentStatus;

    /**
     * State - postęp workflow
     * @ORM\Column(type="json_array", nullable=true)
     */
    protected $state;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $stage;

    /**
     * @var string
     */
    protected $translatedStatus;

    /**
     * @var string
     */
    protected $translatedPaymentStatus;

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param $status
     * @return $this
     */
    public function setStatus($status)
    {

        $this->status = $status;

        return $this;
    }

    /**
     * @param $state
     * @return $this
     */
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * @return array
     */
    public function getState()
    {
        return $this->state;
    }

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
     * @param $stage
     * @return $this
     */
    public function setStage($stage)
    {
        $this->stage = $stage;

        return $this;
    }

    /**
     * @return int
     */
    public function getStage()
    {
        return $this->stage;
    }

    /**
     * @return |null
     */
    public function getMappedStage()
    {
        if (isset(self::$stageList[$this->stage])) {
            return self::$stageList[$this->stage];
        } else {
            return null;
        }
    }


    /**
     * @return string|null
     */
    public function getTranslatedStatus(): ?string
    {
        return $this->translatedStatus;
    }

    /**
     * @param string $translatedStatus
     * @return $this
     */
    public function setTranslatedStatus(string $translatedStatus)
    {
        $this->translatedStatus = $translatedStatus;

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
    public function setPaymentStatus(?int $paymentStatus)
    {
        $this->paymentStatus = $paymentStatus;
        return $this;
    }

    /**
     * @return string
     */
    public function getTranslatedPaymentStatus(): string
    {
        return $this->translatedPaymentStatus;
    }

    /**
     * @param string $translatedPaymentStatus
     * @return $this
     */
    public function setTranslatedPaymentStatus(string $translatedPaymentStatus)
    {
        $this->translatedPaymentStatus = $translatedPaymentStatus;
        return $this;
    }
}
