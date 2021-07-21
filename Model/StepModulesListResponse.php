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
     * @Groups({"Default", "EDI_User", "SHOP_Public"})
     * @SerializedName("currentStep")
     *
     * @var int
     */
    protected $currentStep;

    /**
     * @Groups({"Default", "EDI_User", "SHOP_Public"})
     * @SerializedName("currentStepCode")
     *
     * @var string|null
     */
    protected $currentStepCode;

    /**
     * @Groups({"Default", "EDI_User", "SHOP_Public"})
     *
     * @var array
     */
    protected $modules;

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
     * @param array $modules
     * @return StepModulesListResponse
     */
    public function setModules(array $modules): StepModulesListResponse
    {
        $this->modules = $modules;
        return $this;
    }


}