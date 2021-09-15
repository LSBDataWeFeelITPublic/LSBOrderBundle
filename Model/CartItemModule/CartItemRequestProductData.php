<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Model\CartItemModule;

use LSB\OrderBundle\Entity\CartItemInterface;
use LSB\UtilityBundle\Attribute\Serialize;
use LSB\UtilityBundle\Value\Value;

#[Serialize]
class CartItemRequestProductData
{
    const PROCESSING_TYPE_CREATED = 10;
    const PROCESSING_TYPE_UPDATED = 20;
    const PROCESSING_TYPE_REMOVED = 30;
    const PROCESSING_TYPE_SKIPPED = 40;

    public function __construct(
        protected ?string            $productUuid,
        protected ?string            $productSetUuid,
        protected ?string            $orderCode,
        protected ?Value             $quantity,
        protected ?Value             $productSetQuantity,
        protected bool               $isSelected = true,
        protected bool               $isSelectedForOption = false,
        protected ?int               $processingType = null,
        protected ?CartItemInterface $cartItem = null,
        protected array              $notifications = []
    ) {
    }

    /**
     * @return $this
     */
    public function markAsCreated(): CartItemRequestProductData
    {
        $this->processingType = self::PROCESSING_TYPE_CREATED;
        return $this;
    }

    /**
     * @return CartItemRequestProductData
     */
    public function markAsUpdated(): CartItemRequestProductData
    {
        $this->processingType = self::PROCESSING_TYPE_UPDATED;
        return $this;
    }

    /**
     * @return CartItemRequestProductData
     */
    public function markAsRemoved(): CartItemRequestProductData
    {
        $this->processingType = self::PROCESSING_TYPE_REMOVED;
        return $this;
    }

    /**
     * @return CartItemRequestProductData
     */
    public function markAsSkipped(): CartItemRequestProductData
    {
        $this->processingType = self::PROCESSING_TYPE_SKIPPED;
        return $this;
    }

    /**
     * @param string|null $content
     * @return $this
     */
    public function createSuccessNotification(?string $content = null): CartItemRequestProductData
    {
        $notification = new Notification(Notification::TYPE_SUCCESS, $content);
        $this->addNotification($notification);

        return $this;
    }

    /**
     * @param string|null $content
     * @return $this
     */
    public function createWarningNotification(?string $content = null): CartItemRequestProductData
    {
        $notification = new Notification(Notification::TYPE_WARNING, $content);
        $this->addNotification($notification);

        return $this;
    }

    /**
     * @param string|null $content
     * @return $this
     */
    public function createDefaultNotification(?string $content = null): CartItemRequestProductData
    {
        $notification = new Notification(Notification::TYPE_DEFAULT, $content);
        $this->addNotification($notification);

        return $this;
    }

    /**
     * @param string|null $content
     * @return $this
     */
    public function createErrorNotification(?string $content = null): CartItemRequestProductData
    {
        $notification = new Notification(Notification::TYPE_ERROR, $content);
        $this->addNotification($notification);

        return $this;
    }

    /**
     * @return bool
     */
    public function isProcessed(): bool
    {
        return (bool)$this->processingType ?? false;
    }

    /**
     * @return string|null
     */
    public function getProductUuid(): ?string
    {
        return $this->productUuid;
    }

    /**
     * @param string|null $productUuid
     * @return CartItemRequestProductData
     */
    public function setProductUuid(?string $productUuid): CartItemRequestProductData
    {
        $this->productUuid = $productUuid;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getProductSetUuid(): ?string
    {
        return $this->productSetUuid;
    }

    /**
     * @param string|null $productSetUuid
     * @return CartItemRequestProductData
     */
    public function setProductSetUuid(?string $productSetUuid): CartItemRequestProductData
    {
        $this->productSetUuid = $productSetUuid;
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
     * @return CartItemRequestProductData
     */
    public function setOrderCode(?string $orderCode): CartItemRequestProductData
    {
        $this->orderCode = $orderCode;
        return $this;
    }

    /**
     * @return Value|null
     */
    public function getQuantity(): ?Value
    {
        return $this->quantity;
    }

    /**
     * @param Value|null $quantity
     * @return CartItemRequestProductData
     */
    public function setQuantity(?Value $quantity): CartItemRequestProductData
    {
        $this->quantity = $quantity;
        return $this;
    }

    /**
     * @return Value|null
     */
    public function getProductSetQuantity(): ?Value
    {
        return $this->productSetQuantity;
    }

    /**
     * @param Value|null $productSetQuantity
     * @return CartItemRequestProductData
     */
    public function setProductSetQuantity(?Value $productSetQuantity): CartItemRequestProductData
    {
        $this->productSetQuantity = $productSetQuantity;
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
     * @return CartItemRequestProductData
     */
    public function setIsSelected(bool $isSelected): CartItemRequestProductData
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
     * @return CartItemRequestProductData
     */
    public function setIsSelectedForOption(bool $isSelectedForOption): CartItemRequestProductData
    {
        $this->isSelectedForOption = $isSelectedForOption;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getProcessingType(): ?int
    {
        return $this->processingType;
    }

    /**
     * @param int|null $processingType
     * @return CartItemRequestProductData
     */
    public function setProcessingType(?int $processingType): CartItemRequestProductData
    {
        $this->processingType = $processingType;
        return $this;
    }

    /**
     * @return CartItemInterface|null
     */
    public function getCartItem(): ?CartItemInterface
    {
        return $this->cartItem;
    }

    /**
     * @param CartItemInterface|null $cartItem
     * @return CartItemRequestProductData
     */
    public function setCartItem(?CartItemInterface $cartItem): CartItemRequestProductData
    {
        $this->cartItem = $cartItem;
        return $this;
    }

    /**
     * @return array
     */
    public function getNotifications(): array
    {
        return $this->notifications;
    }

    /**
     * @param ${ENTRY_HINT} $notification
     *
     * @return CartItemRequestProductData
     */
    public function addNotification(Notification $notification): CartItemRequestProductData
    {
        if (false === in_array($notification, $this->notifications, true)) {
            $this->notifications[] = $notification;
        }
        return $this;
    }

    /**
     * @param ${ENTRY_HINT} $notification
     *
     * @return CartItemRequestProductData
     */
    public function removeNotification(Notification $notification): CartItemRequestProductData
    {
        if (true === in_array($notification, $this->notifications, true)) {
            $index = array_search($notification, $this->notifications);
            array_splice($this->notifications, $index, 1);
        }
        return $this;
    }

    /**
     * @param array $notifications
     * @return CartItemRequestProductData
     */
    public function setNotifications(array $notifications): CartItemRequestProductData
    {
        $this->notifications = $notifications;
        return $this;
    }


}