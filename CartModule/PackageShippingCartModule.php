<?php
declare(strict_types=1);

namespace LSB\OrderBundle\CartModule;

use LSB\LocaleBundle\Manager\TaxManager;
use LSB\OrderBundle\Calculator\DefaultShippingMethodCalculator;
use LSB\OrderBundle\CartComponent\CartItemCartComponent;
use LSB\OrderBundle\CartComponent\DataCartComponent;
use LSB\OrderBundle\CartComponent\PackageShippingCartComponent;
use LSB\OrderBundle\Entity\CartInterface;
use LSB\OrderBundle\Entity\CartPackage;
use LSB\OrderBundle\Interfaces\ShippingFormCartCalculatorInterface;
use LSB\OrderBundle\Model\CartModuleProcessResult;
use LSB\OrderBundle\Model\CartShippingMethodCalculatorResult;
use LSB\ShippingBundle\Entity\Method;
use LSB\ShippingBundle\Entity\MethodInterface;
use LSB\UtilityBundle\Helper\ValueHelper;
use LSB\UtilityBundle\Module\ModuleInterface;
use LSB\UtilityBundle\Value\Value;
use Money\Money;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PackageShippingCartModule extends BaseCartModule
{
    const NAME = 'packageShipping';

    /**
     * @var null|array
     */
    protected ?array $customerShippingForms = null;
    /**
     * @var null|array
     */
    protected ?array $shippingForms = null;

    public function __construct(
        DataCartComponent                      $dataCartComponent,
        protected PackageShippingCartComponent $packageShippingCartComponent,
        protected CartItemCartComponent        $cartItemCartComponent
    ) {
        parent::__construct($dataCartComponent);
    }

    /**
     * @param CartInterface|null $cart
     * @param Request $request
     * @return CartModuleProcessResult
     * @throws \Exception
     */
    public function process(?CartInterface $cart, Request $request)
    {
        if (!$cart) {
            $cart = $this->dataCartComponent->getCart();
        }

        $result = null;

        $form = $this->getDefaultForm($cart);

        if ($form instanceof FormInterface) {
            $status = Response::HTTP_NOT_ACCEPTABLE;

            if (!$cart) {
                $cart = $this->getCart();
            }

            $formSubmitResult = $this->handleDefaultFormSubmit($cart, $request);

            if ($formSubmitResult->isSuccess()) {
                $status = Response::HTTP_OK;
                $this->dataCartComponent->getCartManager()->flush();
            } else {
                $result = $formSubmitResult->getForm();
            }
        } else {
            $status = Response::HTTP_OK;
        }

        return new CartModuleProcessResult($result, $status);
    }

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function validate(CartInterface $cart): array
    {
        $errors = [];

        $shippingForms = $this->getShippingForms($cart);
        $cartPackages = $cart->getCartPackages();
        $packageShippingFormValidated = null;

        /**
         * @var CartPackage $cartPackage
         */
        foreach ($cartPackages as $cartPackage) {
            $selectedShippingForm = $cartPackage->getShippingMethod();

            /**
             * @var Method $shippingForm
             */
            foreach ($shippingForms as $shippingForm) {
                if ($shippingForm instanceof Method && $selectedShippingForm === $shippingForm && $shippingForm->isEnabled()) {
                    $packageShippingFormValidated = true;
                    break;
                } elseif ($shippingForm instanceof Method && $selectedShippingForm === $shippingForm && !$shippingForm->isEnabled()) {
                    $packageShippingFormValidated = false;
                }
            }

            if ($packageShippingFormValidated === false) {
                $cartPackage->setShippingMethod(null);
                $errors[] = $this->dataCartComponent->getTranslator()->trans('Cart.Module.PackageShipping.Validation.ShippingFormNotAllowed', [], 'Cart');
            }
        }

        //Jeżeli mamy tylko jedną metodą dostawy, zmieniamy ją automatycznie
        if (!$packageShippingFormValidated && count($shippingForms) === 1) {
            $availableShippingForm = reset($shippingForms);

            /**
             * @var CartPackage $cartPackage
             */
            foreach ($cartPackages as $cartPackage) {
                if ($availableShippingForm instanceof Method) {
                    $cartPackage->setShippingMethod($availableShippingForm);
                }
            }
        }

        //Weryfikujemy czy metoda dostawy została ustawiona
        /**
         * @var CartPackage $cartPackage
         */
        foreach ($cartPackages as $cartPackage) {
            if (!$cartPackage->getShippingMethod()) {
                $errors[] = $this->dataCartComponent->getTranslator()->trans('Cart.Module.PackageShipping.Validation.MissingShippingForm', [], 'Cart');
                break;
            }
        }

        return $errors;
    }

    /**
     * @param CartInterface $cart
     * @param array|null $shippingForms
     * @return Method|null
     * @throws \Exception
     */
    protected function determineDefaultShippingForm(CartInterface $cart, ?array $shippingForms = null): ?Method
    {
        $defaultShipping = null;

        if ($shippingForms === null) {
            $shippingForms = $this->getShippingForms($cart);
        }


        if (count($shippingForms) === 1) {
            $defaultShipping = reset($shippingForms);
        }

        return $defaultShipping;
    }

    /**
     * Metoda przetwarza koszt dostawy dla poszczególnej paczki
     *
     * @param CartPackage $package
     * @param array $availableShippingForms
     * @return array
     * @throws \Exception
     */
    public function processShippingFormsCalculationsForPackage(CartPackage $package, array $availableShippingForms = []): array
    {
        $processedShippingForms = [];

        /**
         * @var Method $shippingForm
         * @var int $key
         */
        foreach ($availableShippingForms as $key => $shippingForm) {
            $calculator = $this->packageShippingCartComponent->getCartCalculatorService()->getCalculator(
                DefaultShippingMethodCalculator::MODULE,
                $package->getShippingMethod() instanceof Method ? $package->getShippingMethod()->getCode() : ModuleInterface::ADDITIONAL_NAME_DEFAULT
            );

            $calculator
                ->setCartPackage($package)
                ->setCart($package->getCart())
                ->setShippingMethod($shippingForm);

            $calculation = $calculator->calculate();
            $processedShippingForms[$shippingForm->getUuid()] = $calculation;
        }

        return $processedShippingForms;
    }

    /**
     * Pobranie dostępnych metod dostawy dla wszystkich paczek
     *
     * @param CartInterface $cart
     * @return array
     * @throws \Exception
     */
    public function getShippingFormsForPackagesWithCalculations(CartInterface $cart): array
    {
        $shippingPackagesCalculations = [];

        if ($cart->getCartPackages()->count() === 0) {
            return [
                [],
                []
            ];
        } else {
            $shippingFormsForPackages = $this->getShippingForms($cart);
            //Dla każdej paczki wyliczamy oddzielnie cenę dostawy
            $availableShippingForms = $this->getShippingForms();
            $availableShippingFormsWithCalculations = [];

            /**
             * @var CartPackage $package
             */
            foreach ($cart->getCartPackages() as $package) {
                $calculations = $this->processShippingFormsCalculationsForPackage(
                    $package,
                    $shippingFormsForPackages
                );

                $package
                    ->setAvailableShippingFormsCalculations($calculations)
                    ->setAvailableShippingForms($availableShippingForms);

                $availableShippingFormsWithCalculations[$package->getUuid()] = $availableShippingForms;
                $shippingPackagesCalculations[$package->getUuid()] = $calculations;
            }

            return [$availableShippingFormsWithCalculations, $shippingPackagesCalculations];
        }
    }

    /**
     * Pobranie dostępnych metod dostawy dla wszystkich paczek
     *
     * @param CartInterface $cart
     * @param array|null $shippingForms
     * @return array
     * @throws \Exception
     */
    public function getShippingFormsCalculationsForPackages(CartInterface $cart, ?array $shippingForms = null): array
    {

        if ($cart->getCartPackages()->count() === 0) {
            return [];
        } else {

            if ($shippingForms === null) {
                $shippingForms = $this->getShippingForms($cart);
            }

            $shippingFormsForPackages = $shippingForms;
            //Dla każdej paczki wyliczamy oddzielnie cenę dostawy
            $availableShippingForms = $shippingForms;

            $availableShippingFormsWithCalculations = [];

            /**
             * @var CartPackage $package
             */
            foreach ($cart->getCartPackages() as $package) {
                $calculations = $this->processShippingFormsCalculationsForPackage(
                    $package,
                    $shippingFormsForPackages
                );

                $package
                    ->setAvailableShippingFormsCalculations($calculations)
                    ->setAvailableShippingForms($availableShippingForms);

                $availableShippingFormsWithCalculations[$package->getUuid()] = $calculations;
            }

            return $availableShippingFormsWithCalculations;
        }
    }

    /*
     * Pobranie dostępnych metod dostawy w zależności od konfiguracji koszyka
     * TODO - do uporządkowania
     */
    /**
     * @param CartInterface|null $cart
     * @return array
     * @throws \Exception
     */
    public function getShippingForms(CartInterface $cart = null): array
    {
        if ($this->shippingForms !== null) {
            return $this->shippingForms;
        }

        $this->shippingForms = $this->getDefaultShippingForms($cart);

        return $this->shippingForms;
    }

    /**
     * Metoda określająca dopuszczalne sposoby dostawy - jako baza wyjściowa do dalszego przetwarzania
     *
     * @param CartInterface $cart
     * @return array
     */
    protected function determineShippingCodes(CartInterface $cart): array
    {
        //TODO zapis kodów w konfiguracji
        return [
            MethodInterface::TYPE_COURIER
        ];
    }

    /**
     * Metoda jest używana do wyliczenia kosztów dostawy podczas sumowania koszyka
     * $calculatedTotalProducts - może być ceną netto lub brutto, w zależności od trybu pracy zliczania
     *
     * @param CartPackage $package
     * @param bool $addVat
     * @param Money|null $calculatedTotalProducts
     * @param array|null $shippingCostRes
     * @return CartShippingMethodCalculatorResult
     * @throws \Exception
     */
    public function calculatePackageShippingCost(
        CartPackage $package,
        bool        $addVat = true,
        ?Money       $calculatedTotalProducts = null,
        array       &$shippingCostRes = null
    ): CartShippingMethodCalculatorResult {
        $packageShippingForm = $package->getShippingMethod();
        $cart = $package->getCart();

        //oblicz cenę
        /**
         * @var ShippingFormCartCalculatorInterface $calculator
         */
        $calculator = $this->packageShippingCartComponent->getCartCalculatorService()->getCalculator('shippingForm', $package->getShippingMethod() ? $package->getShippingMethod()->getCode() : ModuleInterface::ADDITIONAL_NAME_DEFAULT);

        $calculator
            ->setCartPackage($package)
            ->setPaymentMethod($packageShippingForm)
            ->setCart($cart);

        if ($this->dataCartComponent->getPs()->get('cart.calculation.gross')) {
            $calculator
                ->setTotalProductsGross($calculatedTotalProducts);
        } else {
            $calculator
                ->setTotalProductsNetto($calculatedTotalProducts);
        }

        /**
         * @var CartShippingMethodCalculatorResult $calculation
         */
        $calculation = $calculator->calculate();

        if ($shippingCostRes !== null) {
            TaxManager::addMoneyValueToNettoRes(
                $calculation->getTaxPercentage() ?? ValueHelper::convertToValue(23), //TODO FIXED value
                $this->dataCartComponent->getPs()->get('cart.calculation.gross') ? $calculation->getPriceGross() : $calculation->getPriceNet(),
                $shippingCostRes
            );
        }

        return $calculation;
    }

    /**
     * @param CartInterface $cart
     */
    public function clearShippingFormData(CartInterface $cart)
    {
        //TODO
        //$cart->clearPackagesDeliveryData();
    }

    /**
     * Pobiera cały słownik metod dostaw
     *
     * @param CartInterface $cart
     * @return array
     */
    public function getDefaultShippingForms(CartInterface $cart): array
    {
        $codes = $this->determineShippingCodes($cart);
        return $this->packageShippingCartComponent->getShippingMethodManager()->getRepository()->getByCodes($codes);
    }

    /**
     * @inheritdoc
     */
    public function getDataForRender(CartInterface $cart, ?Request $request = null): array
    {
        $parentData = parent::getDataForRender($cart, $request);

        [$shippingFormsForPackages, $calculations] = $this->getShippingFormsForPackagesWithCalculations($cart);

        $data = [
            'shippingFormsForPackages' => $shippingFormsForPackages,
            'calculations' => $calculations
        ];

        return array_merge($parentData, $data);
    }

    /**
     * @param CartInterface $cart
     * @return array
     */
    protected function getResponse(CartInterface $cart): array
    {
//        /**
//         * @var BaseCartDataModule $cartDataModule
//         */
//        $cartDataModule = $this->moduleManager->getModuleByName(BaseCartDataModule::NAME);
//
//        /**
//         * @var BasePaymentModule $paymentModule
//         */
//        $paymentModule = $this->moduleManager->getModuleByName(BasePaymentModule::NAME);
//
//        return [
//            'cart' => $cartDataModule->processCartToArray($cart),
//            'payment' => $paymentModule->processPaymentsToArray($cart)
//        ];
    }
}
