<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Calculator;

use LSB\LocaleBundle\Entity\CountryInterface;
use LSB\LocaleBundle\Manager\TaxManager;
use LSB\OrderBundle\CartHelper\PriceHelper;
use LSB\OrderBundle\Entity\Cart;
use LSB\OrderBundle\Entity\CartInterface;
use LSB\OrderBundle\Interfaces\PaymentMethodCartCalculatorInterface;
use LSB\OrderBundle\Model\CartCalculatorResult;
use LSB\OrderBundle\Model\CartPaymentMethodCalculatorResult;
use LSB\OrderBundle\Model\CartShippingMethodCalculatorResult;
use LSB\PaymentBundle\Entity\Method;
use LSB\PaymentBundle\Entity\Method as PaymentMethod;
use LSB\PricelistBundle\Model\Price;
use LSB\ProductBundle\Entity\ProductInterface;
use LSB\UtilityBundle\Helper\ValueHelper;
use Money\Money;

class DefaultPaymentMethodCalculator extends BaseCartCalculator implements PaymentMethodCartCalculatorInterface
{
    const MODULE = 'paymentMethod';

    const NAME = self::MODULE;

    protected ?Method $paymentMethod = null;

    protected ?CountryInterface $country = null;

    protected ?Money $totalProductsNetto = null;

    protected ?Money $totalProductsGross = null;

    /**
     * @param PriceHelper $priceHelper
     */
    public function __construct(protected PriceHelper $priceHelper)
    {
    }

    public function getModule(): string
    {
        return static::MODULE;
    }

    /**
     * @return Method|null
     */
    public function getPaymentMethod(): ?Method
    {
        return $this->paymentMethod;
    }

    /**
     * @param Method|null $paymentMethod
     * @return DefaultPaymentMethodCalculator
     */
    public function setPaymentMethod(?Method $paymentMethod): DefaultPaymentMethodCalculator
    {
        $this->paymentMethod = $paymentMethod;
        return $this;
    }


    /**
     * @param Money|null $totalProductsNetto
     * @return $this
     */
    public function setTotalProductsNetto(?Money $totalProductsNetto): static
    {
        $this->totalProductsNetto = $totalProductsNetto;

        return $this;
    }

    /**
     * @param Money|null $totalProductsGross
     * @return $this
     */
    public function setTotalProductsGross(?Money $totalProductsGross): static
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
    }

    /**
     * @return CartCalculatorResult|null
     * @throws \Exception
     */
    public function calculate(): ?CartCalculatorResult
    {
        $cart = $this->getCart();

        if (!$this->getPaymentMethod() instanceof Method) {
            $this->paymentMethod = $cart->getPaymentMethod();
        }

        [$totalNet, $totalGross] = $this->calculatePaymentCost($cart, $this->paymentMethod, true);

        return new CartPaymentMethodCalculatorResult(
            $totalNet,
            $totalGross,
            null,
            ValueHelper::convertToValue(1),
            $this->paymentMethod
        );
    }

    /**
     * @param CartInterface $cart
     * @param PaymentMethod|null $method
     * @param bool $addVat
     * @return array
     * @throws \Exception
     */
    public function calculatePaymentCost(CartInterface $cart, ?Method $method, bool $addVat = true): array
    {
        $totalNetto = ValueHelper::convertToMoney(0, $cart->getCurrencyIsoCode());
        $totalGross = ValueHelper::convertToMoney(0, $cart->getCurrencyIsoCode());

        if ($method instanceof PaymentMethod
            && $method->getProduct() instanceof ProductInterface
            && $method->getProduct()->getType() === ProductInterface::TYPE_PAYMENT
        ) {
            $price = $this->priceHelper->getPriceForProduct(
                $cart,
                $method->getProduct(),
                null,
                ValueHelper::convertToValue(1)
            );

            if ($price instanceof Price) {
                $totalNetto = $totalNetto->add($price->getNetPrice(true));
                $totalGross = $totalGross->add($price->getGrossPrice(true));
            }

        }

        return [$totalNetto, $totalGross];
    }
}
