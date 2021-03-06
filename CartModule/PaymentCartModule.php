<?php
declare(strict_types=1);

namespace LSB\OrderBundle\CartModule;

use LSB\LocaleBundle\Manager\TaxManager;
use LSB\OrderBundle\CartComponent\DataCartComponent;
use LSB\OrderBundle\CartComponent\PaymentCartComponent;
use LSB\OrderBundle\Entity\CartInterface;
use LSB\OrderBundle\Event\CartEvent;
use LSB\OrderBundle\Event\CartEvents;
use LSB\OrderBundle\Form\CartModule\Payment\PaymentType;
use LSB\OrderBundle\Interfaces\PaymentMethodCartCalculatorInterface;
use LSB\OrderBundle\Manager\CartManager;
use LSB\OrderBundle\Model\CartModuleProcessResult;
use LSB\OrderBundle\Model\CartPaymentMethodCalculatorResult;
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

    const FORM_CLASS = PaymentType::class;

    public function __construct(
        CartManager                    $cartManager,
        DataCartComponent              $dataCartComponent,
        protected PaymentCartComponent $paymentCartComponent
    ) {
        parent::__construct($cartManager, $dataCartComponent,);
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

                //Dokonany zmiany lub ustalenia pierwszej warto??ci sposobu dostawy
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

        //W przypadku gdy mamy wybran?? metod?? p??atno??ci, a nie jest ona ju?? dost??pna
        $cart->setPaymentMethod(null);

        //Pr??bujemy w takiej sytuacji ustali?? domy??ln?? p??atno????
        $defaultPaymentMethod = $this->determineDefaultPaymentMethod($paymentMethods, $cart->getUser());

        if ($defaultPaymentMethod) {
            $cart->setPaymentMethod($defaultPaymentMethod);
        } else {
            $errors[] = $this->dataCartComponent->getTranslator()->trans('Cart.Module.Payment.Validation.PaymentMethodNotSelected', [], 'LSBOrderBundleCart');
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

        $this->validate($cart);

        if (!$cart->getPaymentMethod() instanceof PaymentMethod) {
            $paymentMethods = $this->getPaymentMethods($cart);
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

    /**
     * @param CartInterface $cart
     * @param PaymentMethod|null $method
     * @param bool $addVat
     * @param Money|null $calculatedTotalProducts
     * @param array|null $paymentCostRes
     * @return CartPaymentMethodCalculatorResult
     * @throws \Exception
     */
    public function calculatePaymentCost(
        CartInterface $cart,
        ?Method        $method,
        bool          $addVat = true,
        ?Money        $calculatedTotalProducts = null,
        array         &$paymentCostRes = null
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
        foreach ($paymentMethods as $paymentMethod) {
            $calculation = $this->calculatePaymentCost(
                $cart,
                $paymentMethod,
                $this->dataCartComponent->addTax($cart),
                $this->dataCartComponent->getPs()->get('cart.calculation.gross') ? $cart->getCartSummary()?->getTotalProductsGross(true) : $cart->getCartSummary()?->getTotalProductsNet(true)
            );

            $calculations[$paymentMethod->getUuid()] = $calculation;
        }

        return $calculations;
    }

    /**
     * @param CartInterface|null $cart
     * @return array
     * @throws \Exception
     */
    protected function getDefaultFormOptions(?CartInterface $cart): array
    {
        if (!$cart instanceof CartInterface) {
            $cart = $this->dataCartComponent->getCart();
        }

        $availablePaymentMethods = $this->getPaymentMethods($cart);
        return ['availablePaymentMethods' => $availablePaymentMethods];
    }

    /**
     * @inheritdoc
     */
    public function getDataForRender(CartInterface $cart, ?Request $request = null): array
    {
        $availablePaymentMethods = $this->getPaymentMethods($cart);
        $calculations = $this->prepareCalculations($cart, $availablePaymentMethods);

        return [
            'allPaymentMethods' => $this->getAllPaymentMethods(), //wszystkie dost??pne metody p??atno??ci
            'availablePaymentMethods' => $availablePaymentMethods, //tylke te dost??pne dla klienta i wynikaj??ce z ustawie?? formularza dostawy dla paczek,
            'selectedPaymentMethod' => $cart->getPaymentMethod(),
            'calculations' => $calculations
        ];
    }
}
