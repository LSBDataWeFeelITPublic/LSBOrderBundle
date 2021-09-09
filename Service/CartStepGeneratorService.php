<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Service;

use LSB\ContractorBundle\Entity\ContractorInterface;
use LSB\OrderBundle\Entity\CartInterface;
use LSB\OrderBundle\Interfaces\CartStepGeneratorInterface;
use LSB\OrderBundle\Model\StepModulesListResponse;
use LSB\OrderBundle\Model\StepModulesResponse;
use LSB\OrderBundle\Model\StepNavigationResult;
use LSB\UserBundle\Entity\UserInterface;
use Symfony\Component\HttpFoundation\Request;

class CartStepGeneratorService
{
    const MODULE_TAG_NAME = 'cart.step.generator';

    public function __construct(
        protected CartStepGeneratorInventory $cartStepGeneratorInventory
    ) {
    }

    /**
     * @param string|int $stepNumber
     * @return CartStepGeneratorInterface|null
     * @throws \Exception
     */
    public function getStepGeneratorByStep(string|int $stepNumber): ?CartStepGeneratorInterface
    {
        $module = $this->cartStepGeneratorInventory->getModuleByName($stepNumber);

        if ($module instanceof CartStepGeneratorInterface) {
            return $module;
        }

        return null;
    }

    /**
     * @param string|int $stepNumber
     * @return mixed
     * @throws \Exception
     */
    public function validateStep(string|int $stepNumber): mixed
    {
        $stepGenerator = $this->getStepGeneratorByStep($stepNumber);

        if (!$stepGenerator) {
            throw new \Exception("StepGenerator $stepNumber not found");
        }

        return $stepGenerator->validate();
    }

    /**
     * Metoda sprawdza, czy dany krok jest dostępny do wyświetlenia
     * W przypadku krok nie jest dostępny zwraca route do którego należy przekierować użytkownika
     * @return array
     */
    public function isStepAccessible(): array
    {
        //TODO WTF?
        return [];
    }

    /**
     * @return array
     */
    public function getSteps(): array
    {
        $steps = [];

        /**
         * @var CartStepGeneratorInterface $step
         */
        foreach ($this->cartStepGeneratorInventory->getModules() as $key => $step) {
            $steps[$step->getStep()] = $step->getModuleList();
        }

        return $steps;
    }

    /**
     * @param string|int $step
     * @param CartInterface|null $cart
     * @param UserInterface|null $user
     * @param ContractorInterface|null $customer
     * @return StepModulesListResponse|null
     * @throws \Exception
     */
    public function getStepModulesList(
        string|int                  $step,
        ?CartInterface       $cart = null,
        ?UserInterface       $user = null,
        ?ContractorInterface $customer = null
    ): ?StepModulesListResponse {
        $cartStepGenerator = $this->getStepGeneratorByStep($step);

        if (!$cartStepGenerator) {
            return null;
        }

        $cartStepGenerator->configure($user, $customer, $cart);
        $cartStepGenerator->prepare();

        return new StepModulesListResponse(
            $cartStepGenerator->getStep(),
            $cartStepGenerator->getCode(),
            $cartStepGenerator->getModuleList()
        );
    }

    /**
     * @param string|int $step
     * @param CartInterface|null $cart
     * @param UserInterface|null $user
     * @param ContractorInterface|null $customer
     * @param Request|null $request
     * @return StepModulesResponse|null
     * @throws \Exception
     */
    public function generateStepModules(
        string|int                  $step,
        ?CartInterface       $cart = null,
        ?UserInterface       $user = null,
        ?ContractorInterface $customer = null,
        ?Request             $request = null
    ): ?StepModulesResponse {
        $cartStepGenerator = $this->getStepGeneratorByStep((string) $step);

        $generatedModules = [];

        if (!$cartStepGenerator) {
            return null;
        }

        //TODO zamienić na obiekt
        [$canAccess, $goToStep] = $cartStepGenerator->isAccessible($cart);

        if ($canAccess && !$cartStepGenerator->getRedirect(false)) {
            $cartStepGenerator->configure($user, $customer, $cart);
            $cartStepGenerator->prepare();
            $generatedModules = $cartStepGenerator->renderModules($request, false);
        }

        return new StepModulesResponse(
            $cartStepGenerator->getStep(),
            $cartStepGenerator->getCode(),
            !$canAccess && $goToStep ? $goToStep : null,
            $cartStepGenerator->getRedirect(false),
            $this->getNavigation((int) $step),
            $cartStepGenerator->isViewable(),
            $generatedModules
        );
    }

    /**
     * @param int $step
     * @return StepNavigationResult
     */
    public function getNavigation(int $step): StepNavigationResult
    {
        $previousStep = $this->getPreviousStep($step);
        $nextStep = $this->getNextStep($step);

        return new StepNavigationResult(
            $previousStep?->getStep(),
            $previousStep?->getCode(),
            $nextStep?->getStep(),
            $nextStep?->getCode()
        );
    }

    /**
     * @param int $currentStep
     * @return CartStepGeneratorInterface|null
     */
    public function getNextStep(int $currentStep)
    {
        $steps = $this->cartStepGeneratorInventory->getModules();

        reset($steps);

        $currentKey = key($steps);

        while ($currentKey !== null && $currentKey != $currentStep) {
            next($steps);
            $currentKey = key($steps);
        }

        $nextStep = next($steps);

        reset($steps);

        if ($nextStep instanceof CartStepGeneratorInterface) {
            return $nextStep;
        }

        return null;
    }


    /**
     * @param int $currentStep
     * @return mixed
     */
    public function getPreviousStep(int $currentStep): mixed
    {
        $steps = $this->cartStepGeneratorInventory->getModules();

        end($steps);
        $currentKey = key($steps);

        while ($currentKey !== null && $currentKey != $currentStep) {
            prev($steps);
            $currentKey = key($steps);
        }

        $prevStep = prev($steps);
        reset($steps);

        if ($prevStep instanceof CartStepGeneratorInterface) {
            return $prevStep;
        }

        return null;
    }
}
