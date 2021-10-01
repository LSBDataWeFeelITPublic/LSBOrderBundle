<?php
declare(strict_types=1);

namespace LSB\OrderBundle\CartGenerator;

use Doctrine\ORM\EntityManagerInterface;
use JetBrains\PhpStorm\ArrayShape;
use LSB\ContractorBundle\Entity\ContractorInterface;
use LSB\OrderBundle\CartModule\CartModuleInterface;
use LSB\OrderBundle\Entity\CartInterface;
use LSB\OrderBundle\Entity\Order;
use LSB\OrderBundle\Entity\OrderInterface;
use LSB\OrderBundle\Interfaces\CartStepGeneratorInterface;
use LSB\OrderBundle\Model\StepNavigationResult;
use LSB\OrderBundle\Model\StepValidationResponse;
use LSB\OrderBundle\Model\StepValidationResult;
use LSB\OrderBundle\Service\CartConverterService;
use LSB\OrderBundle\Service\CartModuleService;
use LSB\OrderBundle\Service\CartService;
use LSB\UserBundle\Entity\UserInterface;
use LSB\UtilityBundle\ModuleInventory\BaseModuleInventory;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

/**
 *
 */
abstract class BaseCartStepGenerator extends BaseModuleInventory implements CartStepGeneratorInterface
{
    const STEP = 0;

    const CODE = 'default';

    const SESSION_CREATED_ORDER_ID_KEY = 'generator/createdOrderId';

    /**
     * @var bool
     */
    protected bool $isPrepared = false;

    /**
     * @var CartInterface|null
     */
    protected ?CartInterface $cart = null;

    /**
     * @var bool
     */
    protected bool $isConfigured = false;

    /**
     * @var int|null
     */
    protected ?int $nextStep = null;

    /**
     * @var int|null
     */
    protected ?int $previousStep = null;

    /**
     * @var bool
     */
    protected bool $isLastStep = false;

    /**
     * @var bool
     */
    protected bool $isCartConverterStep = false;

    /**
     * @param CartModuleService $moduleService
     * @param CartService $cartManager
     * @param EntityManagerInterface $em
     * @param CartConverterService $cartConverter
     * @param RequestStack $requestStack
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        protected CartModuleService        $moduleService,
        protected CartService              $cartManager,
        protected EntityManagerInterface   $em,
        protected CartConverterService     $cartConverter,
        protected RequestStack             $requestStack,
        protected EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return (string)static::STEP;
    }

    /**
     * @return string
     */
    public function getAdditionalName(): string
    {
        return (string)static::ADDITIONAL_NAME_DEFAULT;
    }

    /**
     * @param CartInterface|null $cart
     * @return bool
     * @throws \Exception
     */
    public function isViewable(?CartInterface $cart = null): bool
    {
        return $this->cartManager->isViewable($cart);
    }

    /**
     * @inheritdoc
     */
    public function configure(
        ?UserInterface       $user = null,
        ?ContractorInterface $customer = null,
        ?CartInterface       $cart = null
    ) {
        if (!$cart instanceof CartInterface) {
            $this->cart = $this->cartManager->getCart(true, $user, $customer);
        } else {
            $this->cart = $cart;
        }

        $this->isConfigured = true;
    }

    /**
     * @inheritDoc
     *
     * @return int
     */
    public function getStep(): int
    {
        return static::STEP;
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return static::CODE;
    }

    /**
     * @inheritdoc
     */
    public function prepare()
    {
        if (!$this->isPrepared) {
            $this->prepareModules();
            $this->em->flush();
            $this->isPrepared = true;
        }
    }

    /**
     * Walidacja poszczególnego kroku pod kątem
     * TODO zamienić na obiekt
     *
     * @return StepValidationResponse
     * @throws \Exception
     */
    public function validate(): StepValidationResponse
    {
        $response = [];
        $processResponse = null;
        $isCartFinalized = false;

        [$canAccess, $goToStep] = $this->isAccessible();

        if (!$canAccess) {
            return new StepValidationResponse(
                $this->getValidationResponse(['isAccessible' => false], 1),
                new StepNavigationResult($this->previousStep, null, $goToStep, null),
                null,
                $isCartFinalized
            );
        }

        //Validation
        [$errors, $totalErrorsCnt] = $this->validateModules($this->cart);
        $validationResponse = $this->getValidationResponse($errors, $totalErrorsCnt);

        //Navigation
        $navigationResponse = $this->getNavigation();

        //Run processing if no errors are found
        if (!$totalErrorsCnt) {
            $processResponse = $this->process();
        }

        if ($this->isCartConverterStep && (
                isset($processResponse['order']['uuid']) && $processResponse['order']['uuid']
                || isset($processResponse['orders'][0]['uuid']) && $processResponse['orders'][0]['uuid']
            )
        ) {
            $isCartFinalized = true;
        }

        $response = new StepValidationResponse(
            $validationResponse,
            $navigationResponse,
            $processResponse,
            $isCartFinalized
        );
        //UWAGA FLUSH!
        $this->em->flush();

        return $response;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function process(): array
    {
        $result = [];

        //Krok został oznaczony jako krok konwersji koszyk->zamówienie
        if ($this->isCartConverterStep) {
            $order = $this->cartConverter->convertCartIntoOrder($this->cart);

            if ($order instanceof OrderInterface) {
                $orderData = [
                    'order' => [
                        'uuid' => (string)$order->getUuid(),
                        'number' => $order->getNumber(),
//                        'hash' => $order->getHash(),
//                        'maskToken' => $order->getMaskToken()
                    ],
                    'orderPackage' => [
                        'uuid' => $order->getOrderPackages()->first() ? (string)$order->getOrderPackages()->first()->getUuid() : null,
                    ],
                ];


                $result = $orderData;

                //Nie działa w przypadku restapi
//                if ($order instanceof Order && $order->getId()) {
//                    $this->requestStack->set(static::SESSION_CREATED_ORDER_ID_KEY, $order->getId());
//                }

//                if ($this->getOrderCreatedEventName()) {
//                    //Dispatch event
//                    $this->eventDispatcher->dispatch(
//                        $this->getOrderCreatedEventName(),
//                        new OrderEvent($order, null, null, [], true, $this->applicationManager->getApplication())
//                    );
//                }
            } else {
                throw new \Exception('Order not created');
            }
        }

        return $result;
    }

    /**
     * @param bool $success
     * @param array $data
     * @return array
     */
    #[ArrayShape(
        [
            'success' => "bool",
            'orderUuid' => "mixed|null",
            'orderPackageUuid' => "mixed|null"
        ]
    )]
    protected function getProcessResponse(bool $success, array $data): array
    {
        return [
            'success' => $success,
            'orderUuid' => $data['orderUuid'] ?? null,
            'orderPackageUuid' => $data['orderPackageUuid'] ?? null,
        ];
    }

    /**
     * @param array $errors
     * @param int $errorsCnt
     * @return StepValidationResult
     */
    protected function getValidationResponse(array $errors, int $errorsCnt = 0): StepValidationResult
    {
        return new StepValidationResult(
            $errors,
            $errorsCnt === 0 ? true : false,
            $errorsCnt
        );
    }

    /**
     * Uruchamia walidację poszczególnych modułów podpiętych pod aktualny krok
     * @param CartInterface $cart
     * @return array
     * @throws \Exception
     */
    protected function validateModules(CartInterface $cart): array
    {
        $modules = $this->getModules();
        $errors = [];
        $totalErrorsCnt = 0;

        if (count($modules)) {
            foreach ($modules as $module) {
                $moduleErrors = $this->validateModule($cart, $module);
                $totalErrorsCnt += count($moduleErrors);
                $errors[$module->getName()] = $moduleErrors;
            }
        }
        //Walidacja zakończona powodzeniem, zatwierdzamy etap danego kroku
        if ($totalErrorsCnt == 0 && (static::STEP - $cart->getValidatedStep() <= CartInterface::CART_STEP)) {
            $cart->setValidatedStep(static::STEP);
        } elseif ($cart->getValidatedStep() > static::STEP) {
            $cart->setValidatedStep(null);
        }

        return [$errors, $totalErrorsCnt];
    }

    /**
     * Przygotowanie modułów
     *
     * @return array
     * @throws \Exception
     */
    protected function prepareModules()
    {
        $modules = $this->getModules();
        $preparedModules = [];

        if (count($modules)) {
            foreach ($modules as $module) {
                $preparedModules[$module->getName()] = $this->prepareModule($this->cart, $module);
            }
        }

        //UWAGA FLUSH!
        $this->em->flush();

        return $preparedModules;
    }

    /**
     * Metoda renderująca wszystkie moduły
     *
     * @param Request|null $request
     * @param bool $doPrepare
     * @return array
     * @throws \Exception
     */
    public function renderModules(?Request $request = null, bool $doPrepare = true): array
    {
        if ($doPrepare) {
            $this->prepare();
        }

        $modules = $this->getModules();

        if (count($modules)) {
            /**
             * @var CartModuleInterface $module
             */
            foreach ($modules as $module) {
                $renderedModules[$module->getName()] = $this->renderModule($this->cart, $module, true, $request);
            }
        }

        return $renderedModules;
    }

    /**
     * Metoda wyzwalająca renderowanie pojedynczego modułu
     *
     * @param CartInterface $cart
     * @param CartModuleInterface $cartModule
     * @param bool $isInitialRender
     * @param Request|null $request
     * @return mixed
     * @throws \Exception
     */
    public function renderModule(
        CartInterface       $cart,
        CartModuleInterface $cartModule,
        bool                $isInitialRender = false,
        ?Request            $request = null
    ) {
        return $this->moduleService->renderModule($cartModule, $cart, null, null, $request, $isInitialRender);
    }

    /**
     * Metoda wyzwalająca przygotowanie danych do pojedynczego modułu
     *
     * @param CartInterface $cart
     * @param CartModuleInterface $cartModule
     * @return mixed
     */
    protected function prepareModule(CartInterface $cart, CartModuleInterface $cartModule)
    {
        return $cartModule->prepare($cart);
    }

    /**
     * Metoda wyzwialająca walidację pojedynczego modułu
     *
     * @param CartInterface $cart
     * @param CartModuleInterface $cartModule
     * @return mixed
     * @throws \Exception
     */
    protected function validateModule(CartInterface $cart, CartModuleInterface $cartModule): array
    {
        return $this->moduleService->validateModule($cartModule, $cart);
    }

    /**
     * Metoda pobierająca dostępne moduły dla aktualnego kroku
     *
     * @return array
     * @throws \Exception
     */
    public function getModules(): array
    {
        $modulesToLoad = $this->getModuleList();

        /**
         * @var string $moduleName
         */
        foreach ($modulesToLoad as $moduleName) {
            $module = $this->getCartModuleByName($moduleName);
            $this->modules[$module->getName()] = $module;
        }

        return $this->modules;
    }

    /**
     * @param string $moduleName
     * @return CartModuleInterface
     * @throws \Exception
     */
    public function getCartModuleByName(string $moduleName): CartModuleInterface
    {
        $module = $this->moduleService->getModuleByName($moduleName);
        if (!($module instanceof CartModuleInterface)) {
            throw new \Exception(sprintf('Module "%s" not found', $moduleName));
        }

        return $module;
    }

    /**
     * @param bool $doPrepare
     * @return null|Response
     * @throws \Exception
     */
    public function getRedirect(bool $doPrepare = true): ?string
    {
        $redirectResponse = null;

        if ($doPrepare) {
            $this->prepare();
        }

        $modules = $this->getModules();

        foreach ($modules as $module) {
            $response = $module->getRedirect();

            if ($response !== null) {
                $redirectResponse = $response;
                //Zwracamy pierwszy redirect response (wg kolejności podpięcia modułów)
                break;
            }
        }

        return $redirectResponse;
    }

    /**
     * @return StepNavigationResult
     */
    public function getNavigation(): StepNavigationResult
    {
        return new StepNavigationResult(
            $this->previousStep,
            null,
            $this->nextStep,
            null
        );
    }

    /**
     * @param CartInterface|null $cart
     * @return array
     */
    public function isAccessible(?CartInterface $cart = null): array
    {
        $cart = $cart ?? $this->cart;
        $isAccessible = false;

        $gotoStep = null;
        if ($cart && $cart->getValidatedStep() >= $this->previousStep) {
            $isAccessible = true;
        } else {
            $gotoStep = $this->previousStep;
        }


        return [$isAccessible, $gotoStep];
    }
}
