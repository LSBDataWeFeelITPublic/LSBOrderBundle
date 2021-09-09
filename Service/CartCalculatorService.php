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
use LSB\UtilityBundle\Module\ModuleInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class CartCalculatorService
{
    const MODULE_TAG_NAME = 'cart.calculator';

    const DEFAULT_CALCULATOR_NAME = ModuleInterface::ADDITIONAL_NAME_DEFAULT;

    public function __construct(
        protected ParameterBagInterface    $ps,
        protected EntityManagerInterface   $em,
        protected TranslatorInterface      $translator,
        protected EventDispatcherInterface $eventDispatcher,
        protected TokenStorageInterface    $tokenStorage,
        protected TaxManager               $taxManager,
        protected PricelistManager         $pricelistManager,
        protected RequestStack             $requestStack,
        protected SerializerInterface      $serializer,
        protected CartCalculatorInventory  $calculatorInventory
    ) {
    }

    /**
     * @param string $moduleName
     * @param string $name
     * @param bool $loadDefault
     * @return CartModuleInterface|null
     *
     * Pobieranie modułu wg wskazanej nazwy
     * @throws \Exception
     */
    public function getCalculator(string $moduleName, string $name = ModuleInterface::ADDITIONAL_NAME_DEFAULT, bool $loadDefault = true): ?CartCalculatorInterface
    {
        $module = $this->calculatorInventory->getModuleByName($moduleName, $name, false);

        if ($module instanceof CartCalculatorInterface) {
            $module->setCoreServices(
                $this->ps,
                $this->em,
                $this->translator,
                $this->pricelistManager,
                $this->eventDispatcher,
                $this->tokenStorage,
                $this->taxManager,
                $this->requestStack,
                $this->serializer
            );

            return $module;

        }

        if ($loadDefault) {
            return $this->getDefaultCalculator($moduleName);
        }

        throw new \Exception('Calculator module '.$moduleName.' was not found');
    }

    /**
     * The method takes the default calculator for the module
     * For an example, if courier shipping module calculator is not available, default shipping module calculator will be returned
     *
     * @param $module
     * @return CartCalculatorInterface
     * @throws \Exception
     */
    protected function getDefaultCalculator($module): CartCalculatorInterface
    {
        $name = self::DEFAULT_CALCULATOR_NAME;
        $calculator = $this->getCalculator($module, $name, false);

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
