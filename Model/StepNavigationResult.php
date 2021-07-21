<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Model;

use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\SerializedName;

/**
 * Class StepNavigationResult
 * @package LSB\CartBundle\Model\
 */
class StepNavigationResult
{
    /**
     * @Groups({"Default", "EDI_User", "SHOP_Public"})
     * @SerializedName("previousStep")
     *
     * @var int
     */
    protected $previousStep;

    /**
     * @Groups({"Default", "EDI_User", "SHOP_Public"})
     * @SerializedName("previousStepCode")
     *
     * @var string
     */
    protected $previousStepCode;

    /**
     * @Groups({"Default", "EDI_User", "SHOP_Public"})
     * @SerializedName("nextStep")
     *
     * @var int
     */
    protected $nextStep;

    /**
     * @Groups({"Default", "EDI_User", "SHOP_Public"})
     * @SerializedName("nextStepCode")
     *
     * @var string
     */
    protected $nextStepCode;

    /**
     * StepNavigationResult constructor.
     * @param int $previousStep
     * @param string $previousStepCode
     * @param int $nextStep
     * @param string $nextStepCode
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
     * @return int
     */
    public function getPreviousStep(): int
    {
        return $this->previousStep;
    }

    /**
     * @param int $previousStep
     * @return StepNavigationResult
     */
    public function setPreviousStep(int $previousStep): StepNavigationResult
    {
        $this->previousStep = $previousStep;
        return $this;
    }

    /**
     * @return string
     */
    public function getPreviousStepCode(): string
    {
        return $this->previousStepCode;
    }

    /**
     * @param string $previousStepCode
     * @return StepNavigationResult
     */
    public function setPreviousStepCode(string $previousStepCode): StepNavigationResult
    {
        $this->previousStepCode = $previousStepCode;
        return $this;
    }

    /**
     * @return int
     */
    public function getNextStep(): int
    {
        return $this->nextStep;
    }

    /**
     * @param int $nextStep
     * @return StepNavigationResult
     */
    public function setNextStep(int $nextStep): StepNavigationResult
    {
        $this->nextStep = $nextStep;
        return $this;
    }

    /**
     * @return string
     */
    public function getNextStepCode(): string
    {
        return $this->nextStepCode;
    }

    /**
     * @param string $nextStepCode
     * @return StepNavigationResult
     */
    public function setNextStepCode(string $nextStepCode): StepNavigationResult
    {
        $this->nextStepCode = $nextStepCode;
        return $this;
    }
}