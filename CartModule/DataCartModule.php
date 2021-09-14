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
        protected DataCartComponent $dataCartComponent
    ) {
        parent::__construct($dataCartComponent);
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

        //Metoda weryfikuje dostępność produktów w koszyku i usuwa produkty, jeżeli przestały być dostępne
        //Usunięcie niedostępnych produktów powinno odbywać się przed wyliczeniem CartSummary, tak aby proces nie odbywał się dwa razy

        //TODO do użycia komponent CartItem
        //$removedUnavailableProducts = $this->removeUnavailableProducts($cart);
        $removedUnavailableProducts = [];

        //Pobieramy wartość koszyka i ustalamy wartości pozycji
        //Do weryfikacji czy tutaj powinno się to odbywać każdorazowo
        if ($getCartSummary) {
            $this->getCartSummary($cart, true);
        }


        $cartItems = $cart->getCartItems();
        //$this->storageManager->clearReservedQuantityArray();

        //odswieżamy notyfikacje i aktualizujemy pozycje w koszyku (np. nastąpiła zmiana stanu mag.)
        $notifications = [];

        /**
         * @var CartItem $cartItem
         */
        foreach ($cartItems as $cartItem) {

            //TODO do użycia komponent CartTime
            //$this->checkQuantityAndPriceForCartItem($cartItem, $notifications);

            if ($cartItem->getId() === null) {
                $cartItemRemoved = true;
            }
        }

        //sprawdzenie domyślnego typu podziału paczek
        //TODO do użycia komponent z paczek
        $this->checkForDefaultCartOverSaleType($cart);
        //TODO do użycia komponent z paczek
        $packagesUpdated = $this->updatePackages($cart);

        if ($packagesUpdated) {
            $this->clearValidatedStep($cart);
        }

        //Jeżeli z koszyka usunięte zostały jakieś produkty, wówczas
        if ($removedUnavailableProducts || $cartItemRemoved || $packagesUpdated) {
            $cart->clearCartSummary();
        }

        if ($flush) {
            $this->em->flush();
        }


        return [$notifications, $cart, $cartItemRemoved || $packagesUpdated];
    }
}
