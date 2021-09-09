<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Interfaces;

use LSB\LocaleBundle\Entity\Country;
use LSB\LocaleBundle\Entity\CountryInterface;
use LSB\OrderBundle\Entity\CartPackageInterface;
use LSB\ShippingBundle\Entity\Method;
use Money\Money;

/**
 * Interface ShippingFormCartCalculatorInterface
 * @package LSB\CartBundle\Interfaces
 */
interface ShippingFormCartCalculatorInterface extends CartCalculatorInterface
{

    /**
     * @param Method|null $shippingMethod
     * @return ShippingFormCartCalculatorInterface
     */
    public function setShippingMethod(?Method $shippingMethod):ShippingFormCartCalculatorInterface;

    /**
     * @param Country|null $country
     * @return ShippingFormCartCalculatorInterface
     */
    public function setCountry(?CountryInterface $country):ShippingFormCartCalculatorInterface;

    /**
     * @param CartPackageInterface $cartPackage
     * @return ShippingFormCartCalculatorInterface
     */
    public function setCartPackage(CartPackageInterface $cartPackage): ShippingFormCartCalculatorInterface;

    /**
     * @param Money|null $totalProductsNetto
     * @return mixed
     */
    public function setTotalProductsNetto(?Money $totalProductsNetto);

    /**
     * @param Money|null $totalProductsGross
     * @return mixed
     */
    public function setTotalProductsGross(?Money $totalProductsGross);
}