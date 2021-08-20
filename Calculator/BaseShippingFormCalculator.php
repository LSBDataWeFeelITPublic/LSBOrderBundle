<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Calculator;

use LSB\LocaleBundle\Entity\Country;
use LSB\LocaleBundle\Entity\CountryInterface;
use LSB\OrderBundle\Entity\CartPackageInterface;
use LSB\OrderBundle\Model\CartCalculatorResult;
use LSB\OrderBundle\Model\CartShippingFormCalculatorResult;
use LSB\ShippingBundle\Entity\Method;

/**
 * Class BaseShippingFormCalculator
 * @package LSB\CartBundle\Calculator
 */
abstract class BaseShippingFormCalculator extends BaseCartCalculator implements ShippingFormCartCalculatorInterface
{
    const MODULE = 'shippingForm';


    protected Method $shippingForm;

    protected CountryInterface $country;

    protected CartPackageInterface $cartPackage;

    protected mixed $totalProductsNetto;

    protected mixed $totalProductsGross;

    public function getShippingForm(): Method
    {
        return $this->shippingForm;
    }

    /**
     * @return Country|null
     */
    public function getCountry(): ?CountryInterface
    {
        return $this->country;
    }

    /**
     * @return CartPackageInterface
     */
    public function getCartPackage(): CartPackageInterface
    {
        return $this->cartPackage;
    }

    /**
     * @param $shippingForm
     * @return ShippingFormCartCalculatorInterface
     */
    public function setShippingForm($shippingForm): ShippingFormCartCalculatorInterface
    {
        $this->shippingForm = $shippingForm;

        return $this;
    }

    /**
     * @param Country|null $country
     * @return ShippingFormCartCalculatorInterface
     */
    public function setCountry(?Country $country): ShippingFormCartCalculatorInterface
    {
        $this->country = $country;

        return $this;
    }

    public function setCartPackage(CartPackageInterface $cartPackage): ShippingFormCartCalculatorInterface
    {
        $this->cartPackage = $cartPackage;

        return $this;
    }

    /**
     * @param float|null $totalProductsNetto
     * @return BaseShippingFormCalculator
     */
    public function setTotalProductsNetto(?float $totalProductsNetto)
    {
        $this->totalProductsNetto = $totalProductsNetto;

        return $this;
    }

    /**
     * @param float|null $totalProductsGross
     * @return BaseShippingFormCalculator
     */
    public function setTotalProductsGross(?float $totalProductsGross)
    {
        $this->totalProductsGross = $totalProductsGross;

        return $this;
    }

    /**
     * @return void
     */
    public function clearCalculationData(): void
    {
        parent::clearCalculationData(); // TODO: Change the autogenerated stub

        $this->cartPackage = null;
        $this->country = null;
        $this->shippingForm = null;
    }

    /**
     * @return CartCalculatorResult
     */
    public function calculate(): ?CartCalculatorResult
    {
        $cart = $this->getCart();


        return new CartShippingFormCalculatorResult();
    }
}