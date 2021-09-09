<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Model;

use LSB\LocaleBundle\Entity\CurrencyInterface;
use LSB\PricelistBundle\Calculator\Result;

class CartTotalResult extends Result
{
    /**
     * @param bool $isSuccess
     * @param CurrencyInterface|null $currency
     * @param float $totalNet
     * @param float $totalGross
     * @param null $subject
     * @param array $calculationRes
     * @param array $calculationProductRes
     * @param array $calculationShippingRes
     * @param array $calculationPaymentCostRes
     * @param CartSummary|null $cartSummary
     */
    public function __construct(
        bool $isSuccess,
        ?CurrencyInterface $currency,
        float $totalNet,
        float $totalGross,
        $subject = null,
        array &$calculationRes = [],
        array &$calculationProductRes = [],
        array &$calculationShippingRes = [],
        array &$calculationPaymentCostRes = [],
        protected ?CartSummary $cartSummary = null
    ) {
        parent::__construct(
            $isSuccess,
            $currency,
            $totalNet,
            $totalGross,
            $subject,
            $calculationRes,
            $calculationProductRes,
            $calculationShippingRes,
            $calculationPaymentCostRes
        );
    }
}