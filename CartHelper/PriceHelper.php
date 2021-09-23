<?php

namespace LSB\OrderBundle\CartHelper;

use LSB\OrderBundle\Entity\Cart;
use LSB\OrderBundle\Entity\CartItemInterface;
use LSB\PricelistBundle\Manager\PricelistManager;
use LSB\PricelistBundle\Model\Price;
use LSB\ProductBundle\Entity\Product;
use LSB\UtilityBundle\Helper\ValueHelper;
use LSB\UtilityBundle\Value\Value;
use Money\Money;

class PriceHelper
{
    public function __construct(protected PricelistManager $pricelistManager) {}

    /**
     * @param float $price
     * @param int $quantity
     * @param bool $round
     * @param int $precision
     * @return float
     */
    public function calculateGrossValue(float $price, int $quantity, bool $round = true, int $precision = 2): float
    {
        $value = round($price, $precision) * $quantity;
        return $round ? round($value, $precision) : $value;
    }

    /**
     * @param Money $grossPrice
     * @param Value $quantity
     * @return Money
     */
    public function calculateMoneyGrossValue(
        Money $grossPrice,
        Value $quantity
    ): Money {
        return $grossPrice->multiply($quantity->getRealStringAmount());
    }

    /**
     * @param float $price
     * @param int $quantity
     * @param int|null $taxPercentage
     * @param bool $round
     * @param int $precision
     * @return float
     */
    public function calculateNetValueFromGrossPrice(
        float $price,
        int   $quantity,
        ?int  $taxPercentage,
        bool  $round = true,
        int   $precision = 2
    ): float {
        $taxPercentage = (int)$taxPercentage;
        $value = round($price, $precision) * $quantity;
        return $round ? round($value / ((100 + $taxPercentage) / 100), $precision) : $value;
    }

    /**
     * @param Money $grossPrice
     * @param Value $quantity
     * @param Value $taxPercentage
     * @return Money
     * @throws \Exception
     */
    public function calculateMoneyNetValueFromGrossPrice(
        Money $grossPrice,
        Value $quantity,
        Value $taxPercentage
    ): Money {
        $precision = ValueHelper::getCurrencyPrecision($grossPrice->getCurrency()->getCode());
        $grossValue = $grossPrice->multiply($quantity->getAmount());
        return $grossValue->divide((string)((ValueHelper::get100Percents($precision) + (int)$taxPercentage->getAmount()) / ValueHelper::get100Percents($precision)));
    }

    /**
     * @param float $price
     * @param int $quantity
     * @param bool $round
     * @param int $precision
     * @return float
     * @deprecated
     */
    public function calculateNetValue(float $price, int $quantity, bool $round = true, int $precision = 2): float
    {
        $value = round($price, $precision) * $quantity;
        return $round ? round($value, $precision) : $value;
    }

    /**
     * @param Money $price
     * @param Value $quantity
     * @return Money
     */
    public function calculateMoneyNetValue(
        Money $price,
        Value $quantity
    ): Money {
        return $price->multiply($quantity->getRealStringAmount());
    }

    /**
     * @param float $price
     * @param int $quantity
     * @param int|null $taxPercentage
     * @param bool $round
     * @param int $precision
     * @return float
     * @deprecated
     */
    public function calculateGrossValueFromNetPrice(
        float $price,
        int   $quantity,
        ?int  $taxPercentage,
        bool  $round = true,
        int   $precision = 2
    ): float {
        $taxPercentage = (int)$taxPercentage;
        $value = round($price, $precision) * $quantity;
        return $round ? round($value * (100 + $taxPercentage) / 100, $precision) : $value;
    }

    /**
     * @param Money $netPrice
     * @param Value $quantity
     * @param Value $taxPercentage
     * @return Money
     * @throws \Exception
     */
    public function calculateMoneyGrossValueFromNetPrice(
        Money $netPrice,
        Value $quantity,
        Value $taxPercentage
    ): Money {
        $precision = ValueHelper::getCurrencyPrecision($netPrice->getCurrency()->getCode());
        $grossValue = $netPrice->multiply($quantity->getAmount());
        return $grossValue->multiply(((ValueHelper::get100Percents($precision) + (int)$taxPercentage->getRealStringAmount()) / ValueHelper::get100Percents($precision)));
    }

    /**
     * @param CartItemInterface $cartItem
     * @return Price|null
     * @throws \Exception
     */
    public function getPriceForCartItem(CartItemInterface $cartItem): ?Price
    {
        if (!$cartItem->getCart()) {
            return null;
        }

        return $this->pricelistManager->getPriceForProduct(
            $cartItem->getProduct(),
            null,
            null,
            $cartItem->getCart()->getCurrency(),
            $cartItem->getCart()->getBillingContractor(),
            $cartItem->getQuantity(true)
        );
    }

    /**
     * @param Cart $cart
     * @param Product $product
     * @param Product|null $productSet
     * @param Value $quantity
     * @return Price|null
     * @throws \Exception
     */
    public function getPriceForProduct(
        Cart     $cart,
        Product  $product,
        ?Product $productSet,
        Value    $quantity
    ): ?Price {
        return $this->pricelistManager->getPriceForProduct(
            $product,
            null,
            null,
            $cart->getCurrency(),
            $cart->getBillingContractor(),
            $quantity
        );
    }
}