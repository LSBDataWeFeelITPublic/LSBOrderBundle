<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Model;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Groups;

/**
 * Class CartModuleConfiguration
 * @package LSB\OrderBundle\Model
 */
class CartModuleConfiguration
{

    /**
     * @var bool
     */
    protected bool $isLiveModeRequired;

    /**
     * @var bool
     */
    protected bool $isViewable;

    /**
     * @var array
     */
    protected array $formSchema;

    /**
     * @var boolean
     */
    protected bool $isSticky;

    /**
     * @var bool
     */
    protected bool $isFrontLiveModeRequired;

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
     * @param ${ENTRY_HINT} $formSchema
     *
     * @return CartModuleConfiguration
     */
    public function addFormSchema($formSchema): CartModuleConfiguration
    {
        if (false === in_array($formSchema, $this->formSchema, true)) {
            $this->formSchema[] = $formSchema;
        }
        return $this;
    }

    /**
     * @param ${ENTRY_HINT} $formSchema
     *
     * @return CartModuleConfiguration
     */
    public function removeFormSchema($formSchema): CartModuleConfiguration
    {
        if (true === in_array($formSchema, $this->formSchema, true)) {
            $index = array_search($formSchema, $this->formSchema);
            array_splice($this->formSchema, $index, 1);
        }
        return $this;
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