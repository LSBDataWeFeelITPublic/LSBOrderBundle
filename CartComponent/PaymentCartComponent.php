<?php
declare(strict_types=1);

namespace LSB\OrderBundle\CartComponent;

use LSB\OrderBundle\Manager\CartManager;
use LSB\OrderBundle\Service\CartCalculatorService;
use LSB\PaymentBundle\Manager\MethodManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class PaymentCartComponent extends BaseCartComponent
{
    const NAME = 'packageShipping';

    public function __construct(
        TokenStorageInterface           $tokenStorage,
        protected MethodManager         $paymentMethodManager,
        protected CartCalculatorService $cartCalculatorService,
        protected CartManager           $cartManager
    ) {
        parent::__construct($tokenStorage);
    }

    /**
     * @return CartCalculatorService
     */
    public function getCartCalculatorService(): CartCalculatorService
    {
        return $this->cartCalculatorService;
    }

    /**
     * @return CartManager
     */
    public function getCartManager(): CartManager
    {
        return $this->cartManager;
    }

    /**
     * @return MethodManager
     */
    public function getPaymentMethodManager(): MethodManager
    {
        return $this->paymentMethodManager;
    }
}