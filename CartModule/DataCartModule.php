<?php
declare(strict_types=1);

namespace LSB\OrderBundle\CartModule;

use LSB\ContractorBundle\Entity\ContractorInterface;
use LSB\LocaleBundle\Manager\TaxManager;
use LSB\OrderBundle\Calculator\CartTotalCalculator;
use LSB\OrderBundle\Calculator\DefaultDataCartCalculator;
use LSB\OrderBundle\CartComponent\DataCartComponent;
use LSB\OrderBundle\Entity\Cart;
use LSB\OrderBundle\Entity\CartInterface;
use LSB\OrderBundle\Entity\CartItem;
use LSB\OrderBundle\Event\CartEvent;
use LSB\OrderBundle\Event\CartEvents;
use LSB\OrderBundle\Manager\CartManager;
use LSB\OrderBundle\Model\CartModuleConfiguration;
use LSB\OrderBundle\Model\CartSummary;
use LSB\OrderBundle\Model\DataCartCalculatorResult;
use LSB\PricelistBundle\Service\TotalCalculatorManager;
use LSB\ProductBundle\Entity\ProductSetProduct;
use LSB\UserBundle\Entity\UserInterface;
use LSB\UtilityBundle\Helper\ValueHelper;
use Money\Currency as MoneyCurrency;
use Money\Money;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class CartDataModule
 * @package LSB\OrderBundle\Module
 */
class DataCartModule extends BaseCartModule
{
    const NAME = 'cartData';

    public function __construct(
        CartManager $cartManager,
        DataCartComponent $dataCartComponent
    ) {
        parent::__construct(
            $cartManager,
            $dataCartComponent
        );
    }

    /**
     * @return DataCartComponent
     */
    public function getDataCartComponent(): DataCartComponent
    {
        return $this->dataCartComponent;
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

    /**
     * @param Cart $cart
     * @param bool $rebuildPackages
     * @return CartSummary|null
     * @throws \Exception
     */
    public function getCartSummary(Cart $cart, bool $rebuildPackages = false): ?CartSummary
    {

        /**
         * @var CartTotalCalculator $calculator
         */
        $calculator = $this->dataCartComponent->getTotalCalculatorManager()->getTotalCalculator($cart);


        $result = $calculator->calculateTotal($cart);

        if (!$result->getResultObject() instanceof CartSummary) {
            throw new \Exception('CartSummary is missing');
        }

        return $result->getResultObject();
    }

    /**
     * @param CartInterface $cart
     * @param Request|null $request
     * @return CartSummary[]|null[]
     * @throws \Exception
     */
    public function getDataForRender(CartInterface $cart, ?Request $request = null): array
    {
        return ['cartSummary' => $this->getCartSummary($cart)];
    }


    /**
     * TODO fix
     *
     * @param Cart|null $cart
     * @param bool $flush
     * @param bool $getCartSummary
     * @return array
     * @throws \Exception
     */
    public function rebuildCart(?Cart $cart = null, bool $flush = true, bool $getCartSummary = true): array
    {
        $cartItemRemoved = false;

        //pobieramy koszyk
        if (!$cart) {
            $cart = $this->getCart(true);
        }

        //Metoda weryfikuje dost??pno???? produkt??w w koszyku i usuwa produkty, je??eli przesta??y by?? dost??pne
        //Usuni??cie niedost??pnych produkt??w powinno odbywa?? si?? przed wyliczeniem CartSummary, tak aby proces nie odbywa?? si?? dwa razy

        //TODO do u??ycia komponent CartItem
        //$removedUnavailableProducts = $this->removeUnavailableProducts($cart);
        $removedUnavailableProducts = [];

        //Pobieramy warto???? koszyka i ustalamy warto??ci pozycji
        //Do weryfikacji czy tutaj powinno si?? to odbywa?? ka??dorazowo
        if ($getCartSummary) {
            $this->getCartSummary($cart, true);
        }


        $cartItems = $cart->getCartItems();
        //$this->storageManager->clearReservedQuantityArray();

        //odswie??amy notyfikacje i aktualizujemy pozycje w koszyku (np. nast??pi??a zmiana stanu mag.)
        $notifications = [];

        /**
         * @var CartItem $cartItem
         */
        foreach ($cartItems as $cartItem) {

            //TODO do u??ycia komponent CartTime
            //$this->checkQuantityAndPriceForCartItem($cartItem, $notifications);

            if ($cartItem->getId() === null) {
                $cartItemRemoved = true;
            }
        }

        //sprawdzenie domy??lnego typu podzia??u paczek
        //TODO do u??ycia komponent z paczek
        $this->checkForDefaultCartOverSaleType($cart);
        //TODO do u??ycia komponent z paczek
        $packagesUpdated = $this->updatePackages($cart);

        if ($packagesUpdated) {
            $this->clearValidatedStep($cart);
        }

        //Je??eli z koszyka usuni??te zosta??y jakie?? produkty, w??wczas
        if ($removedUnavailableProducts || $cartItemRemoved || $packagesUpdated) {
            $cart->clearCartSummary();
        }

        if ($flush) {
            $this->em->flush();
        }


        return [$notifications, $cart, $cartItemRemoved || $packagesUpdated];
    }

    /**
     * @param CartInterface $cart
     * @param bool $flush
     */
    public function closeCart(CartInterface $cart, bool $flush = true): void
    {
        $this->dataCartComponent->closeCart($cart, $flush);
    }
}
