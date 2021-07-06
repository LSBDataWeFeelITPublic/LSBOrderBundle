<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\MappedSuperclass;
use LSB\UtilityBundle\Traits\CreatedUpdatedTrait;
use LSB\UtilityBundle\Traits\UuidTrait;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class OrderNote
 * @package LSB\OrderBundle\Entity
 * @MappedSuperclass
 */
abstract class OrderNote implements OrderNoteInterface
{
    use UuidTrait;
    use CreatedUpdatedTrait;

    /**
     * @var array|string[]
     */
    public static array $typeList = [
        self::TYPE_USER_NOTE => 'Order.Note.Type.UserNote',
        self::TYPE_USER_DELIVERY_NOTE => 'Order.Note.Type.UserDeliveryNote',
        self::TYPE_USER_VERIFICATION_REQUEST_NOTE => 'Order.Note.Type.UserVerificationRequestNote',
        self::TYPE_USER_INVOICE_NOTE => 'Order.Note.Type.UserInvoiceNote',
        self::TYPE_USER_NAME => 'Order.Note.Type.UserName',
        self::TYPE_MODERATOR_REJECT_NOTE => 'Order.Note.Type.ModeratorRejectNote',
        self::TYPE_SELLER_NOTE => 'Order.Note.Type.SellerNote',
        self::TYPE_SELLER_VERIFICATION_NOTE => 'Order.Note.Type.SellerVerificationNote',
        self::TYPE_AUTO_GENERATED_NOTE => 'Order.Note.Type.AutoGenerated',
        self::TYPE_AUTO_PRODUCT_SET_NOTE => 'Order.Note.Type.AutoProductSetNote',
        self::TYPE_INTERNAL_NOTE => 'Order.Note.Type.InternalNote',
    ];

    /**
     * @var OrderInterface|null
     * @ORM\ManyToOne(targetEntity="LSB\OrderBundle\Entity\OrderInterface", inversedBy="notes")
     */
    protected ?OrderInterface $order = null;

    /**
     * @var OrderPackageInterface|null
     * @ORM\ManyToOne(targetEntity="LSB\OrderBundle\Entity\OrderPackageInterface", inversedBy="notes")
     */
    protected ?OrderPackageInterface $orderPackage = null;

    /**
     * @var string|null
     * @ORM\Column(type="text", nullable=true)
     */
    protected ?string $content = null;

    /**
     * @var integer
     * @ORM\Column(type="integer", nullable=true)
     */
    protected int $type = self::TYPE_USER_NOTE;

    /**
     * OrderNote constructor.
     * @param null $type
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
     * @return array|string[]
     */
    public static function getTypeList(): array
    {
        return self::$typeList;
    }

    /**
     * @param array|string[] $typeList
     */
    public static function setTypeList(array $typeList): void
    {
        self::$typeList = $typeList;
    }

    /**
     * @return OrderInterface|null
     */
    public function getOrder(): ?OrderInterface
    {
        return $this->order;
    }

    /**
     * @param OrderInterface|null $order
     * @return OrderNote
     */
    public function setOrder(?OrderInterface $order): OrderNote
    {
        $this->order = $order;
        return $this;
    }

    /**
     * @return OrderPackageInterface|null
     */
    public function getOrderPackage(): ?OrderPackageInterface
    {
        return $this->orderPackage;
    }

    /**
     * @param OrderPackageInterface|null $orderPackage
     * @return OrderNote
     */
    public function setOrderPackage(?OrderPackageInterface $orderPackage): OrderNote
    {
        $this->orderPackage = $orderPackage;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     * @param string|null $content
     * @return OrderNote
     */
    public function setContent(?string $content): OrderNote
    {
        $this->content = $content;
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
     * @return OrderNote
     */
    public function setType(int $type): OrderNote
    {
        $this->type = $type;
        return $this;
    }
}
