<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Interfaces;

use LSB\ContractorBundle\Entity\ContractorInterface;
use LSB\OrderBundle\CartModule\CartModuleInterface;
use LSB\OrderBundle\Entity\CartInterface;
use LSB\OrderBundle\Model\StepNavigationResult;
use LSB\OrderBundle\Model\StepValidationResponse;
use LSB\UserBundle\Entity\UserInterface;
use LSB\UtilityBundle\Module\ModuleInterface;
use Symfony\Component\HttpFoundation\Request;

interface CartStepGeneratorInterface extends ModuleInterface
{
    /**
     * Returns the id of the step (int)
     * @return int
     */
    public function getStep(): int;

    /**
     * Returns step identifier (string)
     * @return string
     */
    public function getCode(): string;

    /**
     * @return StepValidationResponse
     */
    public function validate(): StepValidationResponse;

    /**
     * @param CartInterface|null $cart
     * @return array
     */
    public function isAccessible(?CartInterface $cart = null): array;

    /**
     * The method prepares the basket to perform a given step. It can run validation of previous steps or check data consistency.
     */
    public function prepare();

    /**
     * Returns a sorted list of rendering modules
     * @return array
     */
    public function getModules(): array;

    /**
     * Returns a list of modules for the given step
     *
     * @return array
     */
    public function getModuleList(): array;

    /**
     * Method for displaying the given modules
     *
     * @param Request|null $request
     * @param bool $doPrepare
     * @return array
     */
    public function renderModules(?Request $request = null, bool $doPrepare = true): array;

    /**
     * @param CartInterface $cart
     * @param CartModuleInterface $module
     * @param bool $isInitalRender
     * @param Request|null $request
     * @return mixed
     */
    public function renderModule(CartInterface $cart, CartModuleInterface $module, bool $isInitalRender, ?Request $request = null);

    /**
     * @param CartInterface|null $cart
     * @return bool
     */
    public function isViewable(?CartInterface $cart = null): bool;


    /**
     * The method checks if the step is generating a redirect. If the step generates a redirect, be sure to use it.
     *
     * @param bool $doPrepare
     * @return string|null
     */
    public function getRedirect(bool $doPrepare = true): ?string;

    /**
     * The method configures the generator in the context of the user and the client
     *
     * @param UserInterface|null $user
     * @param ContractorInterface|null $customer
     * @param CartInterface|null $cart
     * @return mixed
     */
    public function configure(?UserInterface $user = null, ?ContractorInterface $customer = null, ?CartInterface $cart = null);

    /**
     * @return StepNavigationResult
     */
    public function getNavigation(): StepNavigationResult;
}