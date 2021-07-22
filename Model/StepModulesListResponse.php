<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Model;

use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\SerializedName;

/**
 * Class StepModulesListResponse
 * @package LSB\CartBundle\Model
 *
 * SerializedName dodane dla pewności, aby nazwy po serializacji pozostały w formacie camelCase
 */
class StepModulesListResponse
{

    /**
     * @var int
     */
    protected int $currentStep;

    /**
     * @var string|null
     */
    protected ?string $currentStepCode;

    /**
     * @var array
     */
    protected array $modules;

    /**
     * StepModulesListResponse constructor.
     * @param int $currentStep
     * @param string|null $currentStepCode
     * @param array $modules
     */
    public function __construct(int $currentStep, ?string $currentStepCode, array $modules)
    {
        $this->currentStep = $currentStep;
        $this->currentStepCode = $currentStepCode;
        $this->modules = $modules;
    }

    /**
     * @return int
     */
    public function getCurrentStep(): int
    {
        return $this->currentStep;
    }

    /**
     * @param int $currentStep
     * @return StepModulesListResponse
     */
    public function setCurrentStep(int $currentStep): StepModulesListResponse
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
     * @return StepModulesListResponse
     */
    public function setCurrentStepCode(?string $currentStepCode): StepModulesListResponse
    {
        $this->currentStepCode = $currentStepCode;
        return $this;
    }

    /**
     * @return array
     */
    public function getModules(): array
    {
        return $this->modules;
    }

    /**
     * @param ${ENTRY_HINT} $module
     *
     * @return StepModulesListResponse
     */
    public function addModule($module): StepModulesListResponse
    {
        if (false === in_array($module, $this->modules, true)) {
            $this->modules[] = $module;
        }
        return $this;
    }

    /**
     * @param ${ENTRY_HINT} $module
     *
     * @return StepModulesListResponse
     */
    public function removeModule($module): StepModulesListResponse
    {
        if (true === in_array($module, $this->modules, true)) {
            $index = array_search($module, $this->modules);
            array_splice($this->modules, $index, 1);
        }
        return $this;
    }

    /**
     * @param array $modules
     * @return StepModulesListResponse
     */
    public function setModules(array $modules): StepModulesListResponse
    {
        $this->modules = $modules;
        return $this;
    }
}