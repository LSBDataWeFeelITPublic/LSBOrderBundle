<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Model;

use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\SerializedName;

/**
 * Class StepModulesResponse
 * @package LSB\CartBundle\Model
 */
class StepModulesResponse
{

    /**
     * @var null|string
     */
    protected ?string $redirectTo;

    /**
     * @var null|int
     */
    protected ?int $currentStep;

    /**
     * @var null|string
     */
    protected ?string $currentStepCode;

    /**
     * @var null|int
     */
    protected ?int $goToStep;

    /**
     * @var mixed
     */
    protected mixed $navigation;

    /**
     * @var bool
     */
    protected bool $isViewable;

    /**
     * @var array
     */
    protected array $renderedModules = [];

    /**
     * StepModulesResponse constructor.
     * @param int $currentStep
     * @param string|null $currentStepCode
     * @param int|null $goToStep
     * @param string|null $redirectTo
     * @param mixed $navigation
     * @param bool $isViewable
     * @param array $renderedModules
     */
    public function __construct(
        int $currentStep,
        ?string $currentStepCode,
        ?int $goToStep,
        ?string $redirectTo,
        mixed $navigation,
        bool $isViewable,
        array $renderedModules = []
    ) {
        $this->redirectTo = $redirectTo;
        $this->currentStep = $currentStep;
        $this->currentStepCode = $currentStepCode;
        $this->goToStep = $goToStep;
        $this->navigation = $navigation;
        $this->isViewable = $isViewable;
        $this->renderedModules = $renderedModules;
    }

    /**
     * @return string|null
     */
    public function getRedirectTo(): ?string
    {
        return $this->redirectTo;
    }

    /**
     * @param string|null $redirectTo
     * @return StepModulesResponse
     */
    public function setRedirectTo(?string $redirectTo): StepModulesResponse
    {
        $this->redirectTo = $redirectTo;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getCurrentStep(): ?int
    {
        return $this->currentStep;
    }

    /**
     * @param int|null $currentStep
     * @return StepModulesResponse
     */
    public function setCurrentStep(?int $currentStep): StepModulesResponse
    {
        $this->currentStep = $currentStep;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCurrentStepCode(): ?string
    {
        return $this->currentStepCode;
    }

    /**
     * @param string|null $currentStepCode
     * @return StepModulesResponse
     */
    public function setCurrentStepCode(?string $currentStepCode): StepModulesResponse
    {
        $this->currentStepCode = $currentStepCode;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getGoToStep(): ?int
    {
        return $this->goToStep;
    }

    /**
     * @param int|null $goToStep
     * @return StepModulesResponse
     */
    public function setGoToStep(?int $goToStep): StepModulesResponse
    {
        $this->goToStep = $goToStep;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getNavigation(): mixed
    {
        return $this->navigation;
    }

    /**
     * @param mixed $navigation
     * @return StepModulesResponse
     */
    public function setNavigation(mixed $navigation): StepModulesResponse
    {
        $this->navigation = $navigation;
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
     * @return StepModulesResponse
     */
    public function setIsViewable(bool $isViewable): StepModulesResponse
    {
        $this->isViewable = $isViewable;
        return $this;
    }

    /**
     * @return array
     */
    public function getRenderedModules(): array
    {
        return $this->renderedModules;
    }

    /**
     * @param ${ENTRY_HINT} $renderedModule
     *
     * @return StepModulesResponse
     */
    public function addRenderedModule($renderedModule): StepModulesResponse
    {
        if (false === in_array($renderedModule, $this->renderedModules, true)) {
            $this->renderedModules[] = $renderedModule;
        }
        return $this;
    }

    /**
     * @param ${ENTRY_HINT} $renderedModule
     *
     * @return StepModulesResponse
     */
    public function removeRenderedModule($renderedModule): StepModulesResponse
    {
        if (true === in_array($renderedModule, $this->renderedModules, true)) {
            $index = array_search($renderedModule, $this->renderedModules);
            array_splice($this->renderedModules, $index, 1);
        }
        return $this;
    }

    /**
     * @param array $renderedModules
     * @return StepModulesResponse
     */
    public function setRenderedModules(array $renderedModules): StepModulesResponse
    {
        $this->renderedModules = $renderedModules;
        return $this;
    }
}