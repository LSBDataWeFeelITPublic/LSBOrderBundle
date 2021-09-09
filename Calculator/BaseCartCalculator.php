<?php

namespace LSB\OrderBundle\Calculator;

use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializerInterface;
use LSB\LocaleBundle\Manager\TaxManager;
use LSB\OrderBundle\Entity\CartInterface;
use LSB\OrderBundle\Interfaces\CartCalculatorInterface;
use LSB\OrderBundle\Service\CartService;
use LSB\PricelistBundle\Manager\PricelistManager;
use LSB\UtilityBundle\ModuleInventory\BaseModuleInventory;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use LSB\OrderBundle\Model\CartCalculatorResult;

/**
 * Class BaseCartCalculator
 * @package LSB\CartBundle\Calculator
 */
abstract class BaseCartCalculator extends BaseModuleInventory implements CartCalculatorInterface
{
    const NAME = 'abstract_calc';
    const MODULE = 'abstract_module';

    protected ParameterBagInterface $ps;

    protected EntityManagerInterface $em;

    protected TranslatorInterface $translator;

    protected CartService $cartService;

    protected PricelistManager $priceListManager;

    protected EventDispatcherInterface $eventDispatcher;

    protected TokenStorageInterface $tokenStorage;

    protected TaxManager $taxManager;

    protected RequestStack $session;

    protected SerializerInterface $serializer;

    protected ?CartInterface $cart = null;

    protected mixed $calculationData;

    public function setCoreServices(
        ParameterBagInterface $ps,
        EntityManagerInterface $em,
        TranslatorInterface $translator,
        PriceListManager $priceListManager,
        EventDispatcherInterface $eventDispatcher,
        TokenStorageInterface $tokenStorage,
        TaxManager $taxManager,
        RequestStack $session,
        SerializerInterface $serializer
    ) {
        $this->ps = $ps;
        $this->em = $em;
        $this->translator = $translator;
        $this->priceListManager = $priceListManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->tokenStorage = $tokenStorage;
        $this->taxManager = $taxManager;
        $this->session = $session;
        $this->serializer = $serializer;
    }


    /**
     * @return string
     */
    public function getName(): string
    {
        return static::NAME;
    }

    public function getAdditionalName(): string
    {
        return self::ADDITIONAL_NAME_DEFAULT;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getModule() . ' ' . $this->getName();
    }

    /**
     * @param CartInterface|null $cart
     * @return CartCalculatorInterface
     */
    public function setCart(?CartInterface $cart): CartCalculatorInterface
    {
        $this->cart = $cart;

        return $this;
    }

    /**
     * @return CartInterface|null
     */
    public function getCart(): ?CartInterface
    {
        return $this->cart;
    }

    /**
     * @param array $configurationData
     */
    public function setCalculationData(array $configurationData): void
    {
        $this->calculationData = $configurationData;
    }

    /**
     * @return CartCalculatorResult|null
     */
    public function calculate(): ?CartCalculatorResult
    {
        return null;
    }


    public function clearCalculationData(): void
    {
        $this->calculationData = null;
    }
}