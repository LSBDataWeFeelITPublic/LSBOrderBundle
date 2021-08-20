<?php
declare(strict_types=1);

namespace LSB\OrderBundle\CartModule;

use LSB\ContractorBundle\Entity\ContractorInterface;
use LSB\OrderBundle\CartComponent\DataCartComponent;
use LSB\OrderBundle\Entity\Cart;
use LSB\OrderBundle\Entity\CartInterface;
use LSB\OrderBundle\Model\CartModuleConfiguration;
use LSB\UserBundle\Entity\UserInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class CartDataModule
 * @package LSB\OrderBundle\Module
 */
class DataCartModule extends BaseCartModule
{
    const NAME = 'cartData';

    public function __construct(
        protected DataCartComponent $dataCartComponent
    ) {
        parent::__construct();
    }

    /**
     * @param CartInterface|null $cart
     * @param Request $request
     */
    public function process(?CartInterface $cart, Request $request)
    {
        //ZAPIS ustawien ?
        return;
    }

    /**
     * @param CartInterface $cart
     */
    public function validateData(CartInterface $cart)
    {
        // TODO: Implement validateData() method.
    }

    /**
     * @inheritDoc
     */
    public function getResponse(CartInterface $cart)
    {

    }

    /**
     * @param Cart $cart
     * @throws \Exception
     */
    public function prepare(CartInterface $cart)
    {
        parent::prepare($cart);

        $user = $this->getUser();

        if ($user && $user->getDefaultBillingContractor() && $cart->getBillingContractorData()->getEmail() === null) {
            $cart->getBillingContractorData()->setEmail($user->getEmail());
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function render(CartInterface $cart, ?Request $request = null, bool $isInitialRender = false)
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getConfiguration(
        CartInterface        $cart,
        ?UserInterface       $user = null,
        ?ContractorInterface $contractor = null,
        ?Request             $request = null,
        bool                 $isInitialRender = false
    ): CartModuleConfiguration {
        return new CartModuleConfiguration(false, false, []);
    }
}
