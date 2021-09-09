<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Interfaces;

use LSB\LocaleBundle\Entity\Country;
use LSB\LocaleBundle\Entity\CountryInterface;
use LSB\PaymentBundle\Entity\Method;
use Money\Money;

interface PaymentMethodCartCalculatorInterface extends CartCalculatorInterface
{

    /**
     * @param Method|null $paymentMethod
     * @return PaymentMethodCartCalculatorInterface
     */
    public function setPaymentMethod(?Method $paymentMethod): self;

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