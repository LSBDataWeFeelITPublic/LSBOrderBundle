<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Model;

use LSB\PaymentBundle\Entity\MethodInterface;
use LSB\UtilityBundle\Attribute\Serialize;
use LSB\UtilityBundle\Value\Value;
use Money\Money;

#[Serialize]
class CartPaymentMethodCalculatorResult extends CartCalculatorResult
{
    public function __construct(
        ?Money                     $priceNetto = null,
        ?Money                     $priceGross = null,
        ?Value                     $taxPercentage = null,
        ?Value                     $calculationQuantity = null,
        protected ?MethodInterface $paymentMethod = null
    ) {
        parent::__construct(
            $priceNetto,
            $priceGross,
            $taxPercentage,
            $calculationQuantity
        );
    }

    /**
     * @return MethodInterface|null
     */
    public function getPaymentMethod(): ?MethodInterface
    {
        return $this->paymentMethod;
    }

    /**
     * @param MethodInterface|null $paymentMethod
     * @return CartPaymentMethodCalculatorResult
     */
    public function setPaymentMethod(?MethodInterface $paymentMethod): CartPaymentMethodCalculatorResult
    {
        $this->paymentMethod = $paymentMethod;
        return $this;
    }
}