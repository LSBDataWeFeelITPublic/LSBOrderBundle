<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Model;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Groups;

/**
 * Class CartModuleConfiguration
 * @package LSB\CartBundle\Model
 */
class CartModuleConfiguration
{

    /**
     * @Groups({"Default", "EDI_User", "EDI_Moderator", "SHOP_Public"})
     *
     * @var bool
     */
    protected $isLiveModeRequired;

    /**
     * @Groups({"Default", "EDI_User", "EDI_Moderator", "SHOP_Public"})
     *
     * @var bool
     */
    protected $isViewable;

    /**
     * @Groups({"Default", "EDI_User", "EDI_Moderator", "SHOP_Public"})
     *
     * @var array
     */
    protected $formSchema;

    /**
     * @Groups({"Default", "EDI_User", "EDI_Moderator", "SHOP_Public"})
     *
     * @var boolean
     */
    protected $isSticky;

    /**
     * @Groups({"Default", "EDI_User", "EDI_Moderator", "SHOP_Public"})
     *
     * @var bool
     */
    protected $isFrontLiveModeRequired;

    /**
     * CartModuleConfiguration constructor.
     * @param bool $isLiveModeRequired
     * @param bool $isViewable
     * @param array $formSchema
     * @param bool $isSticky
     * @param bool $isFrontLiveModeRequired
     */
    public function __construct(
        bool $isLiveModeRequired = false,
        bool $isViewable = true,
        array $formSchema = [],
        bool $isSticky = false,
        bool $isFrontLiveModeRequired = false

    ) {
        $this->isLiveModeRequired = $isLiveModeRequired;
        $this->isViewable = $isViewable;
        $this->formSchema = $formSchema;
        $this->isSticky = $isSticky;
        $this->isFrontLiveModeRequired = $isFrontLiveModeRequired;
    }

    /**
     * @return bool
     */
    public function isLiveModeRequired(): bool
    {
        return $this->isLiveModeRequired;
    }

    /**
     * @param bool $isLiveModeRequired
     * @return CartModuleConfiguration
     */
    public function setIsLiveModeRequired(bool $isLiveModeRequired): CartModuleConfiguration
    {
        $this->isLiveModeRequired = $isLiveModeRequired;
        return $this;
    }

    /**
     * @return bool
     */
    public function isViewable(): bool
    {
        return $this->isViewable;
    }

    /**
     * @param bool $isViewable
     * @return CartModuleConfiguration
     */
    public function setIsViewable(bool $isViewable): CartModuleConfiguration
    {
        $this->isViewable = $isViewable;
        return $this;
    }

    /**
     * @return array
     */
    public function getFormSchema(): array
    {
        return $this->formSchema;
    }

    /**
     * @param array $formSchema
     * @return CartModuleConfiguration
     */
    public function setFormSchema(array $formSchema): CartModuleConfiguration
    {
        $this->formSchema = $formSchema;
        return $this;
    }

    /**
     * @return bool
     */
    public function isSticky(): bool
    {
        return $this->isSticky;
    }

    /**
     * @param bool $isSticky
     * @return CartModuleConfiguration
     */
    public function setIsSticky(bool $isSticky): CartModuleConfiguration
    {
        $this->isSticky = $isSticky;
        return $this;
    }

    /**
     * @return bool
     */
    public function isFrontLiveModeRequired(): bool
    {
        return $this->isFrontLiveModeRequired;
    }

    /**
     * @param bool $isFrontLiveModeRequired
     * @return CartModuleConfiguration
     */
    public function setIsFrontLiveModeRequired(bool $isFrontLiveModeRequired): CartModuleConfiguration
    {
        $this->isFrontLiveModeRequired = $isFrontLiveModeRequired;
        return $this;
    }
}