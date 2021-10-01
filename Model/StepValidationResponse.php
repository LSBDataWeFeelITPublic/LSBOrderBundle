<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Model;

use LSB\UtilityBundle\Attribute\Serialize;

#[Serialize]
class StepValidationResponse
{
    public function __construct(
        protected StepValidationResult $validation,
        protected StepNavigationResult $navigation,
        protected $process,
        protected bool $isCartFinalized
    ) {}

    /**
     * @return StepValidationResult
     */
    public function getValidation(): StepValidationResult
    {
        return $this->validation;
    }

    /**
     * @param StepValidationResult $validation
     * @return StepValidationResponse
     */
    public function setValidation(StepValidationResult $validation): StepValidationResponse
    {
        $this->validation = $validation;
        return $this;
    }

    /**
     * @return StepNavigationResult
     */
    public function getNavigation(): StepNavigationResult
    {
        return $this->navigation;
    }

    /**
     * @param StepNavigationResult $navigation
     * @return StepValidationResponse
     */
    public function setNavigation(StepNavigationResult $navigation): StepValidationResponse
    {
        $this->navigation = $navigation;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getProcess()
    {
        return $this->process;
    }

    /**
     * @param mixed $process
     * @return StepValidationResponse
     */
    public function setProcess($process)
    {
        $this->process = $process;
        return $this;
    }

    /**
     * @return bool
     */
    public function isCartFinalized(): bool
    {
        return $this->isCartFinalized;
    }

    /**
     * @param bool $isCartFinalized
     * @return StepValidationResponse
     */
    public function setIsCartFinalized(bool $isCartFinalized): StepValidationResponse
    {
        $this->isCartFinalized = $isCartFinalized;
        return $this;
    }
}