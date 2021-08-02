<?php
declare(strict_types=1);

namespace LSB\OrderBundle\CartModule;

use LSB\ContractorBundle\Entity\ContractorInterface;
use LSB\OrderBundle\Entity\CartInterface;
use LSB\OrderBundle\Model\CartModuleConfiguration;
use LSB\OrderBundle\Model\FormSubmitResult;
use LSB\UserBundle\Entity\UserInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Interface CartModuleInterface
 * @package LSB\OrderBundle\CartModule
 */
interface CartModuleInterface
{
    const RENDER_FORMAT_HTML = 'html';
    const RENDER_FORMAT_JSON = 'json';
    const RENDER_FORMAT_XML = 'xml';

    /**
     * Zwraca nazwę modułu
     * Returns the name of the module
     *
     * @return null|string
     */
    public function getName();

    /**
     * Renders basic element of the module
     *
     * In case of TWIG-based applications, the HTML code will be returned.
     * In case of API-based applications, the JSON result will be returned.
     *
     * @param CartInterface $cart
     * @param null|Request $request
     * @param bool $isInitialRender
     * @return mixed
     */
    public function render(CartInterface $cart, ?Request $request = null, bool $isInitialRender = false);

    /**
     * Performs basic data processing of the module
     *
     * @param CartInterface|null $cart
     * @param Request $request
     * @return mixed
     */
    public function process(?CartInterface $cart, Request $request);

    /**
     * Custom data processing method for API-based applications
     *
     * @param CartInterface|null $cart
     * @param Request $request
     * @return mixed
     */
    public function apiProcess(?CartInterface $cart, Request $request);

    /**
     * Realizuje walidację poprawności danych
     *
     * @param CartInterface $cart
     * @return array
     */
    public function validate(CartInterface $cart): array;

    /**
     * Validates module dependent elements
     * Used when changing the configuration steps.
     *
     * @param CartInterface $cart
     * @return void
     */
    public function validateDependencies(CartInterface $cart): void;

    /**
     * Gets and prepares necesarry data for rendering base element of the module
     *
     * @param CartInterface $cart
     * @param null|Request $request
     * @return array
     */
    public function getDataForRender(CartInterface $cart, ?Request $request = null): array;

    /**
     * Gets and prepares necessary data for serialization.
     *
     * @param CartInterface $cart
     * @param null|Request $request
     * @return array
     */
    public function getDataForSerialize(CartInterface $cart, ?Request $request = null): array;

    /**
     * Metoda wykonuje niezbędne czynności przed rozpoczęciem renderowania modułu
     * The method performs necessary steps before the module starts rendering.
     *
     * @param CartInterface $cart
     * @return mixed
     */
    public function prepare(CartInterface $cart): mixed;

    /**
     * The module verifies that the module is correctly configured and ready to use.
     *
     * @return bool
     */
    public function isConfigured(): bool;

    /**
     * The method gets a redirect response from the module
     *
     * @return null|string
     */
    public function getRedirect(): ?string;

    /**
     * Gets the list of the modules to be refreshed after the request was successfully processed.
     *
     * @param CartInterface|null $cart
     * @param Request|null $request
     * @return array
     */
    public function getModulesToRefresh(?CartInterface $cart = null, ?Request $request = null): array;


    /**
     * Returns module configuration
     *
     * @param CartInterface $cart
     * @param UserInterface|null $user
     * @param ContractorInterface|null $contractor
     * @param Request|null $request
     * @param bool $isInitialRender
     * @return CartModuleConfiguration
     */
    public function getConfiguration(
        CartInterface $cart,
        ?UserInterface $user = null,
        ?ContractorInterface $contractor = null,
        ?Request $request = null,
        bool $isInitialRender = false
    ): CartModuleConfiguration;

    /**
     * Returns form schema for API-based applications
     *
     * @param CartInterface $cart
     * @param UserInterface|null $user
     * @param ContractorInterface|null $customer
     * @param Request|null $request
     * @param bool $isInitialRender
     * @return array
     */
    public function getFormSchema(
        CartInterface $cart,
        ?UserInterface $user = null,
        ?ContractorInterface $customer = null,
        ?Request $request = null,
        bool $isInitialRender = false
    ): array;

    /**
     * The method checks whether the module is viewable or not.
     *
     * @param CartInterface $cart
     * @param UserInterface|null $user
     * @param ContractorInterface|null $customer
     * @param Request|null $request
     * @param bool $isInitialRender
     * @return bool
     */
    public function isViewable(
        CartInterface $cart,
        ?UserInterface $user = null,
        ?ContractorInterface $customer = null,
        ?Request $request = null,
        bool $isInitialRender = false
    ): bool;

    /**
     * The method checks whether the module is accessible or not.
     *
     * @param CartInterface $cart
     * @param UserInterface|null $user
     * @param ContractorInterface|null $customer
     * @param Request|null $request
     * @param bool $isInitialRender
     * @return bool
     */
    public function isAccessible(
        CartInterface $cart,
        ?UserInterface $user = null,
        ?ContractorInterface $customer = null,
        ?Request $request = null,
        bool $isInitialRender = false
    ): bool;

    /**
     * Returns default form type of the module
     *
     * @param $dataObject
     * @param array $options
     * @param bool $useCart
     * @return FormInterface|null
     */
    public function getDefaultForm(
        $dataObject,
        array $options = [],
        bool $useCart = true
    ): ?FormInterface;

    /**
     * Returns default form type class of the module
     *
     * @return string|null
     */
    public function getDefaultFormClass(): ?string;


    /**
     * Performs a form request handling
     *
     * @param CartInterface $cart
     * @param Request $request
     * @return FormSubmitResult
     */
    public function handleDefaultFormSubmit(CartInterface $cart, Request $request): FormSubmitResult;

    /**
     * Dispatches success event
     *
     * @param CartInterface $cart
     */
    public function dispatchSuccessEvent(CartInterface $cart): void;

    /**
     * Dispatches failure event
     *
     * @param CartInterface $cart
     */
    public function dispatchFailEvent(CartInterface $cart): void;

    /**
     * Returns the list of the dedicated serialization groups (if necessary)
     *
     * @param CartInterface $cart
     * @param Request|null $request
     * @return array
     */
    public function getSerializationGroups(CartInterface $cart, ?Request $request = null): array;

    /**
     * Returns list of the modules, which rendering should be skipped during dependant modules refreshment.
     *
     * @param CartInterface $cart
     * @param Request|null $request
     * @return array
     */
    public function blockDataRenderWhileRefreshing(CartInterface $cart, ?Request $request): array;
}
