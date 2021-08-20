<?php

namespace LSB\OrderBundle\Interfaces;

use LSB\CartBundle\Entity\CartPackage;
use LSB\LocaleBundle\Entity\Country;
use LSB\OrderBundle\Entity\CartPackageInterface;

/**
 * Interface ShippingFormCartCalculatorInterface
 * @package LSB\CartBundle\Interfaces
 */
interface ShippingFormCartCalculatorInterface extends CartCalculatorInterface
{

    /**
     * @param $shippingForm
     * @return ShippingFormCartCalculatorInterface
     */
    public function setShippingForm($shippingForm):ShippingFormCartCalculatorInterface;

    /**
     * @param Country|null $country
     * @return ShippingFormCartCalculatorInterface
     */
    public function setCountry(?Country $country):ShippingFormCartCalculatorInterface;

    /**
     * @param CartPackageInterface $cartPackage
     * @return ShippingFormCartCalculatorInterface
     */
    public function setCartPackage(CartPackageInterface $cartPackage): ShippingFormCartCalculatorInterface;

    /**
     * @param float|null $totalProductsNetto
     * @return mixed
     */
    public function setTotalProductsNetto(?float $totalProductsNetto);

    /**
     * @param float|null $totalProductsGross
     * @return mixed
     */
    public function setTotalProductsGross(?float $totalProductsGross);
}