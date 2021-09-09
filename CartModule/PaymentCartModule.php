<?php
declare(strict_types=1);

namespace LSB\OrderBundle\CartModule;

use LSB\LocaleBundle\Manager\TaxManager;
use LSB\OrderBundle\CartComponent\DataCartComponent;
use LSB\OrderBundle\CartComponent\PaymentCartComponent;
use LSB\OrderBundle\Entity\CartInterface;
use LSB\OrderBundle\Entity\CartPackage;
use LSB\OrderBundle\Event\CartEvent;
use LSB\OrderBundle\Event\CartEvents;
use LSB\OrderBundle\Interfaces\PaymentMethodCartCalculatorInterface;
use LSB\OrderBundle\Interfaces\ShippingFormCartCalculatorInterface;
use LSB\OrderBundle\Model\CartModuleProcessResult;
use LSB\OrderBundle\Model\CartPaymentMethodCalculatorResult;
use LSB\OrderBundle\Model\CartShippingMethodCalculatorResult;
use LSB\OrderBundle\Model\FormSubmitResult;
use LSB\PaymentBundle\Entity\Method;
use LSB\UserBundle\Entity\UserInterface;
use LSB\UtilityBundle\Helper\ValueHelper;
use LSB\UtilityBundle\Module\ModuleInterface;
use Money\Money;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use LSB\PaymentBundle\Entity\Method as PaymentMethod;

class PaymentCartModule extends BaseCartModule
{
    const NAME = 'payment';

    public function __construct(
        DataCartComponent              $dataCartComponent,
        protected PaymentCartComponent $paymentCartComponent
    ) {
        parent::__construct($dataCartComponent,);
    }

    /**
     * @inheritDoc
     * @return CartModuleProcessResult|mixed
     * @throws \Exception
     */
    public function process(?CartInterface $cart, Request $request)
    {
        if (!$cart) {
            $cart = $this->getCart();
        }

        $result = null;

        $form = $this->getDefaultForm($cart);

        $currentPaymentMethod = $cart->getPaymentMethod();

        if ($form instanceof FormInterface) {
            $status = Response::HTTP_NOT_ACCEPTABLE;

            $formSubmitResult = $this->handleDefaultFormSubmit($cart, $request);

            if ($formSubmitResult->isSuccess()) {
                $status = Response::HTTP_OK;
                $this->dataCartComponent->getCartManager()->flush();

                //Dokonany zmiany lub ustalenia pierwszej wartości sposobu dostawy
                if ($currentPaymentMethod !== $cart->getPaymentMethod()) {
                    $this->dataCartComponent->getEventDispatcher()->dispatch(
                        new CartEvent(
                            $cart
                        ),
                        CartEvents::CART_PAYMENT_METHOD_CHANGED
                    );
                }
            } else {
                $result = $formSubmitResult->getForm();
            }
        } else {
            $status = Response::HTTP_OK;
        }

        return new CartModuleProcessResult($result, $status);
    }

    /**
     * @param CartInterface $cart
     * @param Request $request
     * @return FormSubmitResult
     * @throws \Exception
     */
    public function handleDefaultFormSubmit(CartInterface $cart, Request $request): FormSubmitResult
    {
        $form = $this->getDefaultForm($cart);

        $result = new FormSubmitResult(
            false,
            $form
        );

        if ($request->getMethod() === Request::METHOD_POST) {
            if ($this->isForApiUsage()) {
                $data = json_decode($request->getContent(), true);
                $form->submit($data);
            } else {
                $form->handleRequest($request);
            }

            if ($form->isValid()) {
                $result = new FormSubmitResult(true, $form);
            } else {
                $result = new FormSubmitResult(
                    false,
                    $form
                );
            }
        }

        return $result;
    }

    /**
     * @param CartInterface $cart
     * @return array
     * @throws \Exception
     */
    public function validate(CartInterface $cart): array
    {
        $errors = [];
        $paymentMethods = $this->getPaymentMethods($cart);
        $selectedPaymentMethod = $cart->getPaymentMethod();

        if ($selectedPaymentMethod instanceof PaymentMethod) {
            /**
             * @var PaymentMethod $paymentMethod
             */
            foreach ($paymentMethods as $paymentMethod) {
                if ($paymentMethod->getId() === $selectedPaymentMethod->getId()) {
                    return [];
                }
            }
        }

        //W przypadku gdy mamy wybraną metodą płatności, a nie jest ona już dostępna
        $cart->setPaymentMethod(null);

        //Próbujemy w takiej sytuacji ustalić domyślną płatność
        $defaultPaymentMethod = $this->determineDefaultPaymentMethod($paymentMethods, $cart->getUser());

        if ($defaultPaymentMethod) {
            $cart->setPaymentMethod($defaultPaymentMethod);
        } else {
            $errors[] = $this->dataCartComponent->getTranslator()->trans('Cart.Module.Payment.Validation.PaymentMethodSelected', [], 'Cart');
        }

        return $errors;
    }

    /**
     * @param CartInterface $cart
     * @return void
     * @throws \Exception
     */
    public function prepare(CartInterface $cart)
    {
        parent::prepare($cart);

        //Walidujemy dostępność metody płatności przed przygotowaniem listy
        $this->validate($cart);

        if (!$cart->getPaymentMethod() instanceof PaymentMethod) {
            $paymentMethods = $this->getPaymentMethods($cart);

            //Próbujemy w takiej sytuacji ustalić domyślną płatność
            $defaultPaymentMethod = $this->determineDefaultPaymentMethod($paymentMethods, $cart->getUser());

            if ($defaultPaymentMethod) {
                $cart->setPaymentMethod($defaultPaymentMethod);
            }
        }
    }

    /**
     * @param array $paymentMethods
     * @param UserInterface|null $user
     * @return PaymentMethod|null
     */
    public function determineDefaultPaymentMethod(array $paymentMethods, ?UserInterface $user = null): ?Method
    {
        if (count($paymentMethods)) {
            $paymentMethod = $paymentMethods[array_key_first($paymentMethods)];

            if ($paymentMethod instanceof Method) {
                return $paymentMethod;
            }
        }

        return null;
    }

    /**
     * @param CartInterface $cart
     * @return array
     */
    public function getPaymentMethods(CartInterface $cart): array
    {
        $formattedPaymentMethods = [];
        $paymentMethods = $this->paymentCartComponent->getPaymentMethodManager()->getRepository()->getEnabled();

        /**
         * @var PaymentMethod $paymentMethod
         */
        foreach ($paymentMethods as $key => $paymentMethod) {
            $formattedPaymentMethods[$paymentMethod->getUuid()] = $paymentMethod;
            unset($paymentMethods[$key]);
        }

        return $formattedPaymentMethods;
    }

    /**
     * Pobiera wszystkie metody płatności dostępne dla koszyka
     *
     * @return array
     */
    public function getAllPaymentMethods(): array
    {
        $formattedPaymentMethods = [];
        $paymentMethods = $this->paymentCartComponent->getPaymentMethodManager()->getRepository()->getAll();

        /**
         * @var PaymentMethod $paymentMethod
         */
        foreach ($paymentMethods as $key => $paymentMethod) {
            $formattedPaymentMethods[$paymentMethod->getUuid()] = $paymentMethod;
            unset($paymentMethods[$key]);
        }

        return $formattedPaymentMethods;
    }

    public function calculatePaymentCost(
        CartInterface $cart,
        Method $method,
        bool        $addVat = true,
        ?Money       $calculatedTotalProducts = null,
        array       &$paymentCostRes = null
    ): CartPaymentMethodCalculatorResult {

        /**
         * @var PaymentMethodCartCalculatorInterface $calculator
         */
        $calculator = $this->paymentCartComponent->getCartCalculatorService()->getCalculator('paymentMethod', $method->getCode() ?? ModuleInterface::ADDITIONAL_NAME_DEFAULT);

        $calculator
            ->setCart($cart)
            ->setPaymentMethod($method);

        if ($this->dataCartComponent->getPs()->get('cart.calculation.gross')) {
            $calculator
                ->setTotalProductsGross($calculatedTotalProducts);
        } else {
            $calculator
                ->setTotalProductsNetto($calculatedTotalProducts);
        }

        /**
         * @var CartPaymentMethodCalculatorResult $calculation
         */
        $calculation = $calculator->calculate();

        if ($paymentCostRes !== null) {
            TaxManager::addMoneyValueToNettoRes(
                $calculation->getTaxPercentage() ?? ValueHelper::convertToValue(23), //TODO FIXED value
                $this->dataCartComponent->getPs()->get('cart.calculation.gross') ? $calculation->getPriceGross() : $calculation->getPriceNet(),
                $paymentCostRes
            );
        }

        return $calculation;
    }

    /**
     * @param CartInterface $cart
     * @param array $paymentMethods
     * @return array
     * @throws \Exception
     */
    public function prepareCalculations(CartInterface $cart, array $paymentMethods): array
    {
        $calculations = [];

        /**
         * @var PaymentMethod $paymentMethod
         */
        foreach ($paymentMethods as $paymentMethod)
        {
            $calculation = $this->calculatePaymentCost(
                $cart,
                $paymentMethod,
                $this->dataCartComponent->addTax($cart),
                $this->dataCartComponent->getPs()->get('cart.calculation.gross') ? $cart->getCartSummary()?->getTotalProductsGross(true) : $cart->getCartSummary()?->getTotalProductsNet()
            );

            $calculations[$paymentMethod->getUuid()] = $calculation;
        }

        return $calculations;
    }

    /**
     * @inheritdoc
     */
    public function getDataForRender(CartInterface $cart, ?Request $request = null): array
    {
        $availablePaymentMethods = $this->getPaymentMethods($cart);
        $calculations = $this->prepareCalculations($cart, $availablePaymentMethods);

        return [
            'allPaymentMethods' => $this->getAllPaymentMethods(), //wszystkie dostępne metody płatności
            'availablePaymentMethods' => $availablePaymentMethods, //tylke te dostępne dla klienta i wynikające z ustawień formularza dostawy dla paczek,
            'selectedPaymentMethod' => $cart->getPaymentMethod(),
            'calculations' => $calculations
        ];
    }
}
