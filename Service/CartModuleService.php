<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializerInterface;
use LSB\ContractorBundle\Entity\ContractorInterface;
use LSB\LocaleBundle\Manager\TaxManager;
use LSB\OrderBundle\CartModule\CartModuleInterface;
use LSB\OrderBundle\Entity\CartInterface;
use LSB\OrderBundle\Interfaces\CartStepGeneratorInterface;
use LSB\OrderBundle\Model\CartModuleProcessResponse;
use LSB\OrderBundle\Model\CartModuleProcessResult;
use LSB\OrderBundle\Model\CartModuleRenderResponse;
use LSB\PricelistBundle\Manager\PricelistManager;
use LSB\ProductBundle\Manager\ProductManager;
use LSB\ProductBundle\Manager\StorageManager;
use LSB\UserBundle\Entity\UserInterface;
use LSB\UserBundle\Manager\UserManager;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/**
 * Class BaseCartModuleManager
 * @package LSB\CartBundle\Service
 */
class CartModuleService
{
    public const CART_MODULE_TAG_NAME = 'cart.module';

    public function __construct(
        protected CartModuleInventory           $cartModuleInventory,
        protected ParameterBagInterface         $ps,
        protected EntityManagerInterface        $em,
        protected TranslatorInterface           $translator,
        protected CartService                   $cartManager,
        protected PriceListManager              $priceListManager,
        protected EventDispatcherInterface      $eventDispatcher,
        protected TokenStorageInterface         $tokenStorage,
        protected ProductManager                $productManager,
        protected TaxManager                    $taxManager,
        protected PricelistManager              $priceManager,
        protected StorageManager                $storageManager,
        protected FormFactoryInterface          $formFactory,
        protected SerializerInterface           $serializer,
        protected UserManager                   $userManager,
        protected ValidatorInterface            $validator,
        protected RouterInterface               $router,
        protected CartCalculatorService         $cartCalculatorManager,
        protected AuthorizationCheckerInterface $authorizationChecker,
        protected CartStepGeneratorService      $cartStepGeneratorManager,
        protected Environment                   $twig,
        protected CartComponentService          $cartComponentService
    ) {
    }

    /**
     * @param string $moduleName
     * @return CartModuleInterface|null
     *
     * Pobieranie modułu wg wskazanej nazwy
     * @throws \Exception
     */
    public function getModuleByName(string $moduleName): ?CartModuleInterface
    {
        $modules = $this->cartModuleInventory->getModules();

        $module = $this->cartModuleInventory->getModuleByName($moduleName);

        if ($module instanceof CartModuleInterface) {
            if (!$module->isConfigured()) {
                throw new \Exception(sprintf('Module "%s" is not configured', $moduleName));
            }

            return $module;
        }

        return null;
    }

    /**
     * @param string $moduleName
     * @return CartModuleInterface|null
     * @throws \Exception
     */
    public function getModuleByClass(string $moduleName): ?CartModuleInterface
    {
        $module = $this->cartModuleInventory->getModuleByName($moduleName);

        if ($module instanceof CartModuleInterface) {
            if (!$module->isConfigured()) {
                throw new \Exception(sprintf('Module "%s" is not configured', $moduleName));
            }

            return $module;
        }

        return null;
    }


    /**
     * @param Request $request
     * @param CartInterface|null $cart
     * @param CartModuleInterface|string $module
     * @param int|null $step
     * @param UserInterface|null $user
     * @param ContractorInterface|null $contractor
     * @return CartModuleProcessResponse
     * @throws \Exception
     */
    public function processModule(
        Request                    $request,
        ?CartInterface             $cart,
        CartModuleInterface|string $module,
        ?int                       $step = null,
        ?UserInterface             $user = null,
        ?ContractorInterface $contractor = null
    ): CartModuleProcessResponse {
        $processedModuleContent = null;
        $processedModuleStatus = Response::HTTP_OK;
        $renderedModules = [];
        $isAccessible = true;

        $module = $this->getCartModule($module);

        if (!$cart) {
            $cart = $this->cartManager->getCart(false, $user, $contractor);
        }

        if ($step !== null) {
            $isAccessible = $this->isStepAccessible($cart, $step);
        }

        if (!$module->isAccessible($cart, $user, $contractor, $request)) {
            $isAccessible = false;
        }

        if (!$isAccessible) {
            //If the step in unavailable, empty result will be returned.
            return new CartModuleProcessResponse(
                null,
                $module->getConfiguration($cart, $user, $contractor, $request),
                [],
                [],
                Response::HTTP_FORBIDDEN
            );
        }


        $response = $module->process($cart, $request);

        //W celu zgodności z istniejącymi modułami, do ujednolicenia
        if (is_array($response) && array_key_exists('content', $response) && array_key_exists('status', $response)) {
            /**
             * @depracated Wszystkie nowe moduły muszą zwracać obiekt zamiast tablicy
             */
            $processedModuleContent = $response['content'];
            $processedModuleStatus = (int)$response['status'];
        } elseif ($response instanceof CartModuleProcessResult) {
            $processedModuleContent = $response->getContent();
            $processedModuleStatus = $response->getStatus();
        } else {
            $processedModuleContent = $response;
        }

        $modulesToRefresh = $this->getModulesToRefresh($module, $cart, $request, $user, $contractor, $step);
        $renderedModules = $processedModuleStatus === Response::HTTP_OK ? $this->renderModulesToRefresh($module, $cart, $request, $user, $contractor, $step) : [];

        return new CartModuleProcessResponse(
            $processedModuleContent,
            $module->getConfiguration($cart, $user, $contractor, $request),
            $modulesToRefresh,
            $renderedModules,
            $processedModuleStatus,
            $module->getSerializationGroups($cart, $request)
        );
    }

    /**
     * @param CartModuleInterface|string $module
     * @param CartInterface $cart
     * @param Request $request
     * @param UserInterface|null $user
     * @param ContractorInterface|null $customer
     * @param int|null $step
     * @param array|null $modulesToRefresh
     * @return array
     * @throws \Exception
     */
    public function renderModulesToRefresh(
        CartModuleInterface|string $module,
        CartInterface              $cart,
        Request                    $request,
        ?UserInterface             $user = null,
        ?ContractorInterface       $customer = null,
        ?int                       $step = null,
        ?array                     $modulesToRefresh = null
    ): array {
        if (!$modulesToRefresh || $modulesToRefresh && !is_array($modulesToRefresh)) {
            $modulesToRefresh = $this->getModulesToRefresh($module, $cart, $request, $user, $customer, $step);
        }

        $module = $this->getCartModule($module);
        $modulesToBlock = $module->blockDataRenderWhileRefreshing($cart, $request);

        $renderedModules = [];

        if (count($modulesToRefresh)) {
            /** @var string $moduleToRefresh */
            foreach ($modulesToRefresh as $moduleToRefresh) {
                $blockDataRender = false;

                if (array_search($moduleToRefresh, $modulesToBlock) !== false) {
                    $blockDataRender = true;
                }

                $renderedModules[$moduleToRefresh] = $this->renderModule($moduleToRefresh, $cart, $user, $customer, $request, $blockDataRender);
            }

            $this->em->flush();
        }

        return $renderedModules;
    }

    /**
     * @param CartModuleInterface|string $module
     * @param CartInterface $cart
     * @param Request|null $request
     * @param UserInterface|null $user
     * @param ContractorInterface|null $customer
     * @param int|null $step
     * @return array
     * @throws \Exception
     */
    public function getModulesToRefresh(
        CartModuleInterface|string $module,
        CartInterface              $cart,
        ?Request                   $request,
        ?UserInterface             $user = null,
        ?ContractorInterface       $customer = null,
        ?int                       $step = null
    ): array {
        $module = $this->getCartModule($module);

        $modulesToRefresh = $module->getModulesToRefresh($cart, $request);

        if ($step) {
            $cartStepGenerator = $this->cartStepGeneratorManager->getStepGeneratorByStep($step);

            //Odświeżenie tylko tych modułów, ktore są aktualnie użyte na wskazanym kroku
            if ($cartStepGenerator instanceof CartStepGeneratorInterface) {
                $modulesInSteps = $cartStepGenerator->getModuleList();
                $modulesToRefresh = array_values(array_intersect($modulesToRefresh, $modulesInSteps));
            }
        }

        return $modulesToRefresh;
    }

    /**
     * @param CartInterface $cart
     * @param int $step
     * @return bool
     * @throws \Exception
     */
    public function isStepAccessible(CartInterface $cart, int $step): bool
    {
        $cartStepGenerator = $this->cartStepGeneratorManager->getStepGeneratorByStep($step);

        if ($cartStepGenerator instanceof CartStepGeneratorInterface) {
            //Ręcznie dokonujemy konfiguracji kroku
            $cartStepGenerator->configure(null, null, $cart);
            [$canAccess, $goToStep] = $cartStepGenerator->isAccessible();

            return $canAccess;
        }

        return false;
    }

    /**
     * Renderowanie pojedycznego modułu
     *
     * @param CartModuleInterface|string $module
     * @param CartInterface $cart
     * @param UserInterface|null $user
     * @param ContractorInterface|null $customer
     * @param null|Request $request
     * @param bool|null $blockDataRender
     * @param bool $renderModulesToRefresh
     * @return mixed
     * @throws \Exception
     */
    public function renderModule(
        CartModuleInterface|string $module,
        CartInterface              $cart,
        ?UserInterface             $user = null,
        ?ContractorInterface       $customer = null,
        ?Request                   $request = null,
        bool                       $blockDataRender = false,
        bool                       $renderModulesToRefresh = false
    ): CartModuleRenderResponse {
        $module = $this->getCartModule($module);

        $module->prepare($cart);

        return new CartModuleRenderResponse(
            $module->render($cart, $request, $blockDataRender),
            $module->getConfiguration($cart, $user, $customer, $request, $blockDataRender),
            $renderModulesToRefresh ? $this->renderModulesToRefresh($module, $cart, $request, $user, $customer, null) : [],
            $this->getModulesToRefresh($module, $cart, $request, $user, $customer),
            $module->getSerializationGroups($cart, $request)
        );
    }

    /**
     * @param $module
     * @param CartInterface|null $cart
     * @param UserInterface|null $user
     * @param ContractorInterface|null $customer
     * @param Request|null $request
     * @param bool $isInitialRender
     * @return array
     * @throws \Exception
     */
    public function getFormSchema(
        $module,
        ?CartInterface $cart,
        ?UserInterface $user = null,
        ?ContractorInterface $customer = null,
        ?Request $request = null,
        bool $isInitialRender = false
    ) {
        $module = $this->getCartModule($module);

        if (!$cart) {
            $cart = $this->cartManager->getCart(true);
        }

        return $module->getFormSchema($cart, $user, $customer, $request, $isInitialRender);
    }

    /**
     * Metoda uruchamia walidację modułu
     *
     * @param CartModuleInterface|string $module
     * @param CartInterface|null $cart
     * @return array
     * @throws \Exception
     */
    public function validateModule(
        CartModuleInterface|string $module,
        ?CartInterface             $cart = null
    ) {
        $module = $this->getCartModule($module);

        if (!$cart) {
            $cart = $this->cartManager->getCart(true);
        }

        return $module->validate($cart);
    }

    /**
     * @param CartModuleInterface|string $module
     * @return CartModuleInterface
     * @throws \Exception
     */
    public function getCartModule(CartModuleInterface|string $module): CartModuleInterface
    {
        if ($module && !$module instanceof CartModuleInterface) {
            $module = $this->getModuleByName((string)$module);
        }

        if (!$module instanceof CartModuleInterface) {
            throw new \Exception(sprintf("Module %s not found", $module));
        }

        return $module;
    }
}
