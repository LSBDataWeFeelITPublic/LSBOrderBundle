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
     * @Groups({"Default", "EDI_User", "SHOP_Public"})
     * @SerializedName("redirectTo")
     *
     * @var null|string
     */
    protected $redirectTo;

    /**
     * @Groups({"Default", "EDI_User", "SHOP_Public"})
     * @SerializedName("currentStep")
     *
     * @var null|integer
     */
    protected $currentStep;

    /**
     * @Groups({"Default", "EDI_User", "SHOP_Public"})
     * @SerializedName("currentStepCode")
     *
     * @var null|string
     */
    protected $currentStepCode;

    /**
     * @Groups({"Default", "EDI_User", "SHOP_Public"})
     * @SerializedName("goToStep")
     *
     * @var null|integer
     */
    protected $goToStep;

    /**
     * @Groups({"Default", "EDI_User", "SHOP_Public"})
     *
     * @var mixed
     */
    protected $navigation;

    /**
     * @Groups({"Default", "EDI_User", "SHOP_Public"})
     * @SerializedName("isViewable")
     *
     * @var bool
     */
    protected $isViewable;

    /**
     * @Groups({"Default", "EDI_User", "SHOP_Public"})
     * @SerializedName("renderedModules")
     *
     * @var array
     */
    protected $renderedModules = [];

    /**
     * StepModulesResponse constructor.
     * @param int|null $currentStep
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
        $navigation,
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
    public function getNavigation()
    {
        return $this->navigation;
    }

    /**
     * @param mixed $navigation
     * @return StepModulesResponse
     */
    public function setNavigation($navigation)
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
     * @param array $renderedModules
     * @return StepModulesResponse
     */
    public function setRenderedModules(array $renderedModules): StepModulesResponse
    {
        $this->renderedModules = $renderedModules;
        return $this;
    }

}