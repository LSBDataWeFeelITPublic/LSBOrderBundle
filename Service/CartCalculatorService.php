<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializerInterface;
use LSB\LocaleBundle\Manager\TaxManager;
use LSB\OrderBundle\CartModule\CartModuleInterface;
use LSB\OrderBundle\Entity\CartInterface;
use LSB\OrderBundle\Interfaces\CartCalculatorInterface;
use LSB\OrderBundle\Model\CartCalculatorResult;
use LSB\PricelistBundle\Manager\PricelistManager;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class CartCalculatorService
 * @package LSB\CartBundle\Service
 */
class CartCalculatorService
{
    const DEFAULT_CALCULATOR_NAME = 'Default';

    //TODO use module repository
    protected array $calculators = [];

    public function __construct(
        protected ParameterBagInterface    $ps,
        protected EntityManagerInterface   $em,
        protected TranslatorInterface      $translator,
        protected CartService              $cartManager,
        protected PricelistManager         $pricelistManager,
        protected EventDispatcherInterface $eventDispatcher,
        protected TokenStorageInterface    $tokenStorage,
        protected TaxManager               $taxManager,
        protected PricelistManager         $priceManager,
        protected SessionInterface         $session,
        protected SerializerInterface      $serializer
    ) {}

    /**
     * @param CartCalculatorInterface $cartCalculator
     * @param array $attrs
     * @return array
     */
    public function addCalculator(CartCalculatorInterface $cartCalculator, array $attrs = []): array
    {
        $this->calculators[$cartCalculator->getModule()][$cartCalculator->getName()] = $cartCalculator;

        return $this->calculators;
    }

    /**
     * @param string $module
     * @param null|string $name
     * @return CartModuleInterface|null
     *
     * Pobieranie modułu wg wskazanej nazwy
     * @throws \Exception
     */
    public function getCalculator(string $module, ?string $name): ?CartCalculatorInterface
    {
        if ($name) {
            foreach ($this->calculators as $calculatorModuleName => $calculators) {
                if ($calculatorModuleName === $module) {
                    foreach ($calculators as $moduleCalculator) {
                        if ($moduleCalculator->getName() === $name) {
                            $moduleCalculator->setCoreServices(
                                $this->ps,
                                $this->em,
                                $this->translator,
                                $this->cartManager,
                                $this->pricelistManager,
                                $this->eventDispatcher,
                                $this->tokenStorage,
                                $this->taxManager,
                                $this->session,
                                $this->serializer
                            );
                            return $moduleCalculator;
                        }
                    }
                }
            }
        }

        return $this->getDefaultCalculator($module);
    }

    /**
     * The method takes the default calculator for the module
     *
     * @param $module
     * @return CartCalculatorInterface
     * @throws \Exception
     */
    protected function getDefaultCalculator($module): CartCalculatorInterface
    {
        $name = self::DEFAULT_CALCULATOR_NAME;

        $calculator = $this->getCalculator($module, $name);

        if (!$calculator instanceof CartCalculatorInterface) {
            throw new \Exception("Default calculator for module {$module} was not found. Please check your configuration and create default calculator for module: {$module}");
        }

        return $calculator;
    }


    /**
     * @param string $module
     * @param string|null $name
     * @param array $calculationData
     * @param CartInterface|null $cart
     * @return CartCalculatorResult|null
     * @throws \Exception
     */
    public function calculate(string $module, ?string $name, array $calculationData = [], ?CartInterface $cart = null): ?CartCalculatorResult
    {
        $calculator = $this->getCalculator($module, $name);

        //W przypadku braku dedykowanego kalkulatora, używany domyślnego lub wskazujemy inny kolejny
        if (!$calculator) {
            $calculator = $this->getDefaultCalculator($module);
        }

        $calculator->setCalculationData($calculationData);
        if (!$cart) {
            $cart = $this->cartManager->getCart();
        }

        $calculator->setCart($cart);

        return $calculator->calculate();
    }
}
