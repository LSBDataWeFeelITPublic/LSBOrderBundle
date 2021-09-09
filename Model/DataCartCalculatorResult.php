<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Model;

use LSB\UtilityBundle\Value\Value;
use Money\Money;

class DataCartCalculatorResult extends CartCalculatorResult
{
    /**
     * CartShippingFormCalulatorResult constructor.
     * @param Money|null $priceNetto
     * @param Money|null $priceGross
     * @param Value|null $taxPercentage
     * @param Value|null $calculationQuantity
     * @param CartSummary|null $cartSummary
     */
    public function __construct(
        ?Money $priceNetto = null,
        ?Money $priceGross = null,
        ?Value $taxPercentage = null,
        ?Value $calculationQuantity = null,
        protected ?CartSummary $cartSummary = null
    ) {
        parent::__construct(
            $priceNetto,
            $priceGross,
            $taxPercentage,
            $calculationQuantity
        );
    }

    /**
     * @return CartSummary|null
     */
    public function getCartSummary(): ?CartSummary
    {
        return $this->cartSummary;
    }

    /**
     * @param CartSummary|null $cartSummary
     * @return DataCartCalculatorResult
     */
    public function setCartSummary(?CartSummary $cartSummary): DataCartCalculatorResult
    {
        $this->cartSummary = $cartSummary;
        return $this;
    }
}