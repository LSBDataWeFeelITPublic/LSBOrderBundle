<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Model;

/**
 * Class StepNavigationResult
 * @package LSB\CartBundle\Model\
 */
class StepNavigationResult
{
    /**
     * @var int|null
     */
    protected ?int $previousStep;

    /**
     * @var string|null
     */
    protected ?string $previousStepCode;

    /**
     * @var int|null
     */
    protected ?int $nextStep;

    /**
     * @var string|null
     */
    protected ?string $nextStepCode;

    /**
     * StepNavigationResult constructor.
     * @param int|null $previousStep
     * @param string|null $previousStepCode
     * @param int|null $nextStep
     * @param string|null $nextStepCode
     */
    public function __construct(
        ?int $previousStep,
        ?string $previousStepCode,
        ?int $nextStep,
        ?string $nextStepCode
    ) {
        $this->previousStep = $previousStep;
        $this->previousStepCode = $previousStepCode;
        $this->nextStep = $nextStep;
        $this->nextStepCode = $nextStepCode;
    }

    /**
     * @return int|null
     */
    public function getPreviousStep(): ?int
    {
        return $this->previousStep;
    }

    /**
     * @param int|null $previousStep
     * @return StepNavigationResult
     */
    public function setPreviousStep(?int $previousStep): StepNavigationResult
    {
        $this->previousStep = $previousStep;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getPreviousStepCode(): ?string
    {
        return $this->previousStepCode;
    }

    /**
     * @param string|null $previousStepCode
     * @return StepNavigationResult
     */
    public function setPreviousStepCode(?string $previousStepCode): StepNavigationResult
    {
        $this->previousStepCode = $previousStepCode;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getNextStep(): ?int
    {
        return $this->nextStep;
    }

    /**
     * @param int|null $nextStep
     * @return StepNavigationResult
     */
    public function setNextStep(?int $nextStep): StepNavigationResult
    {
        $this->nextStep = $nextStep;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getNextStepCode(): ?string
    {
        return $this->nextStepCode;
    }

    /**
     * @param string|null $nextStepCode
     * @return StepNavigationResult
     */
    public function setNextStepCode(?string $nextStepCode): StepNavigationResult
    {
        $this->nextStepCode = $nextStepCode;
        return $this;
    }
}