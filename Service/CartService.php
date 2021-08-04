<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use LSB\CartBundle\Event\CartEvent;
use LSB\ContractorBundle\Entity\Contractor;
use LSB\ContractorBundle\Entity\ContractorInterface;
use LSB\LocaleBundle\Entity\CurrencyInterface;
use LSB\LocaleBundle\Manager\TaxManager;
use LSB\OrderBundle\Entity\Cart;
use LSB\OrderBundle\Entity\CartInterface;
use LSB\OrderBundle\Entity\CartItem;
use LSB\OrderBundle\Event\CartEvents;
use LSB\OrderBundle\Manager\CartManager;
use LSB\OrderBundle\Module\CartDataModule;
use LSB\OrderBundle\Repository\CartRepository;
use LSB\PricelistBundle\Manager\PricelistManager;
use LSB\ProductBundle\Entity\Product;
use LSB\ProductBundle\Manager\ProductManager;
use LSB\ProductBundle\Manager\StorageManager;
use LSB\UserBundle\Entity\User;
use LSB\UserBundle\Entity\UserInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Webmozart\Assert\Assert;

/**
 * Class BaseCartManager
 * @package LSB\OrderBundle\Service
 */
class CartService
{
    const CART_SESSION_ID_ATTR_NAME = 'cart/SessionId';
    const CART_TRANSACTION_ID_ATTR_NAME = 'cart/TransactionId';
    const CART_SESSION_RULES_ACCEPTED_ATTR_NAME = 'cart/RulesAccepted';
    const CART_SESSION_ID_HEADER_KEY = 'cart-session-id';
    const CART_TRANSACTION_ID_HEADER_KEY = 'cart-transaction-id';

    /**
     * @var CartInterface|null
     * @deprecated
     */
    protected ?CartInterface $cart = null;

    /**
     * @var bool|null
     * @deprecated
     */
    protected ?bool $addTax = null;

    /**
     * @var array
     */
    protected array $cartItemTypeUpdated = [];

    /**
     * BaseCartManager constructor.
     * @param CartManager $cartManager
     * @param EntityManagerInterface $em
     * @param TranslatorInterface $translator
     * @param TokenStorageInterface $tokenStorage
     * @param SessionInterface $session
     * @param ParameterBagInterface $ps
     * @param ProductManager $productManager
     * @param StorageManager $storageManager
     * @param PriceListManager $priceListManager
     * @param TaxManager $taxManager
     * @param CartModuleManager $moduleManager
     * @param EventDispatcherInterface $eventDispatcher
     * @param RequestStack $requestStack
     */
    public function __construct(
        protected CartManager              $cartManager,
        protected EntityManagerInterface   $em,
        protected TranslatorInterface      $translator,
        protected TokenStorageInterface    $tokenStorage,
        protected SessionInterface         $session,
        protected ParameterBagInterface    $ps,
        protected ProductManager           $productManager,
        protected StorageManager           $storageManager,
        protected PriceListManager         $priceListManager,
        protected TaxManager               $taxManager,
        protected CartModuleManager        $moduleManager,
        protected EventDispatcherInterface $eventDispatcher,
        protected RequestStack             $requestStack
    ) {
    }

    /**
     * TODO
     * @return mixed
     */
    public function getDefaultDetalPriceType()
    {
        return null;
    }

    /**
     * TODO
     * @param bool $getId
     * @return int|null
     */
    public function getDefaultCataloguePriceType(bool $getId = false)
    {
        return null;
    }

    /**
     * @param bool|null $forceReload
     * @param User|null $user
     * @param Contractor|null $contractor
     * @param bool $requireCreate
     * @return Cart|null
     * @throws \Exception
     */
    public function getCart(
        ?bool       $forceReload = false,
        ?User       $user = null,
        ?Contractor $contractor = null,
        bool        $requireCreate = true
    ): ?Cart {
        if (!$user) {
            $user = $this->getUser();
        }

        //TODO default contractor
        if (!$contractor && $user instanceof User) {
            $contractor = $user->getDefaultBillingContractor();
        }

        $flush = false;

        if ($forceReload) {
            $this->cart = null;
        }

        if ($this->cart === null) {
            $sessionId = $this->getCartSessionId();
            $transactionId = $this->getCartTransactionId();
            $storedCart = null;

            /**
             * Weryfikujemy obecność koszyka sesyjnego
             * @var null|Cart $sessionCart
             */
            $sessionCart = $this->cartManager->getRepository()->getCartForUser(
            //$this->applicationManager->getApplication()->getId(),
                null,
                null,
                $sessionId,
                $this->ps->get('cart.valid.days'),
                CartInterface::CART_TYPE_SESSION
            );

            if ($user instanceof UserInterface) {
                /**
                 * @var null|Cart $storedCart
                 */
                $storedCart = $this->cartManager->getRepository()->getCartForUser(
                    $user->getId(),
                    $contractor ? $contractor->getId() : $contractor,
                    null,
                    $this->ps->get('cart.valid.days'),
                    CartInterface::CART_TYPE_USER
                );
            }

            if ($storedCart && $storedCart->getSessionId()) {
                $storedCart->setSessionId(null);
            }

            if ($transactionId && $sessionCart instanceof Cart && $sessionCart->getTransactionId() !== $transactionId) {
                $sessionCart->setTransactionId($transactionId);
            }

            if ($transactionId && $storedCart instanceof Cart && $storedCart->getTransactionId() !== $transactionId) {
                $storedCart->setTransactionId($transactionId);
            }

            //Scalamy koszyk, jeżeli istnieją dwa
            if ($sessionCart && $storedCart) {
                //w ramach jednego użytkownika istnieją dwa koszyki, sprobujmy je scalić
                $this->cart = $this->mergeCarts($sessionCart, $storedCart);
            } elseif (!$sessionCart && $storedCart) {
                $this->cart = $storedCart;
            } elseif ($sessionCart && !$storedCart && $user) {
                $this->convertSessionCartToUserCart($sessionCart, $user);
                $this->cart = $sessionCart;
            } elseif ($sessionCart) {
                $this->cart = $sessionCart;
            }

            if (!($this->cart instanceof Cart)) {
                //Tworzymy nowy obiekt koszyka
                $this->cart = $this->createNewCart($user, $contractor, $sessionId, $transactionId);
                $flush = true;
            }
        }

        $flush += $this->checkAndUpdateCartCurrency($this->cart);

        if ($requireCreate && $flush || $this->cart->getId() && !$this->cart->getReportCode()) {
            $this->cart->generateReportCode();
            $flush = true;
        }

        if ($requireCreate && !$this->cart->getId() && $flush || $this->cart->getId() && $flush) {
            //Add the reporting code only before the flush
            $this->em->flush();
        }

        return $this->cart;
    }

    /**
     * TODO
     *
     * @param Cart $cart
     * @return bool
     * @throws \Exception
     */
    public function checkAndUpdateCartCurrency(Cart $cart): bool
    {
        return true;
    }

    /**
     * Pobranie porzuconego koszyka
     *
     * @param string $uuid
     * @return Cart|null
     */
    public function getAbandonedCart(string $uuid): ?Cart
    {
        try {
            Assert::uuid($uuid);
            return $this->getCartRepository()->getAbandonedCart($uuid);
        } catch (\Exception $e) {
        }

        return null;
    }

    public function getCartByUuid(string $uuid): ?Cart
    {
        return $this->cartManager->getByUuid($uuid);
    }


    public function getCartByTransactionId(string $uuid): ?CartInterface
    {
        return $this->getCartRepository()->getByTransactionId($uuid);
    }

    /**
     * @param string $oldSessionId
     * @return Cart
     * @throws \Exception
     * @deprecated
     */
    public function getReassignSessionCart(string $oldSessionId): ?CartInterface
    {
        $sessionCart = $this->cartManager->getRepository()->findOneBy(
            [
                'sessionId' => $oldSessionId,
            ]
        );

        $newSessionId = $this->getCartSessionId();

        if ($sessionCart instanceof Cart) {
            $sessionCart->setSessionId($newSessionId);
        }

        //Note, a flush is being performed
        $this->cartManager->update($sessionCart);

        return $sessionCart;
    }

    public function assignRegisteredUser(UserInterface $user, ?CartInterface $cart = null): Cart
    {
        if (!$cart) {
            $cart = $this->getCart();
        }

        $cart
            ->setUser($user)
            ->setBillingContractor($user->getDefaultBillingContractor());

        //An important change, we do a flush right away
        $this->cartManager->update($cart);

        return $cart;
    }

    public function unsetUser(?Cart $cart = null): CartInterface
    {
        if (!$cart) {
            $cart = $this->getCart();
        }

        $cart
            ->setUser(null)
            ->setBillingContractor(null);
        //TODO clear string

        //An important change, we do a flush right away
        $this->cartManager->update($cart);

        return $cart;
    }

    /**
     *
     *
     * @param UserInterface|null $user
     * @param ContractorInterface|null $customer
     * @return Currency|null
     * @throws \Exception
     */
    protected function getDefaultCurrencyForCart(
        ?UserInterface       $user = null,
        ?ContractorInterface $customer = null
    ): ?CurrencyInterface {
        if (!$user) {
            $user = $this->getUser();
        }

        if ($user && !$user instanceof UserInterface) {
            throw new \Exception('User class is not supported');
        }


        if (!$customer && $user instanceof UserInterface) {
            $customer = $user->getDefaultBillingContractor();
        }

        //TODO
        return $this->priceListManager->getCurrency($customer);
    }

    protected function createNewCart(
        ?UserInterface       $user,
        ?ContractorInterface $contractor,
        string               $sessionId,
        ?string              $transactionId = null
    ): CartInterface {

        $cart = (new Cart)
            ->setSessionId($sessionId)
            ->setTransactionId($transactionId)
            ->setCurrency($this->getDefaultCurrencyForCart($user, $contractor));

        if ($user instanceof UserInterface) {
            //The shopping cart is associated with the customer and the user
            $cart
                ->setBillingContractor($contractor)
                ->setUser($user)
                ->setType(CartInterface::CART_TYPE_USER);
        } else {
            $cart
                ->setType(CartInterface::CART_TYPE_SESSION);
        }

        $this->em->persist($cart);

        $this->eventDispatcher->dispatch(
            new CartEvent(
                $cart
            ),
            CartEvents::CART_CREATED,

        );

        return $cart;
    }

    /**
     * 
     *
     * @param Cart $sessionCart
     * @param Cart $storedCart
     * @return Cart
     * @throws \Exception
     */
    public function mergeCarts(Cart $sessionCart, Cart $storedCart): Cart
    {

        $cartDataModule = $this->moduleManager->getModuleByName(CartDataModule::NAME);
        //Kasujemy cache naliczania vatu
        $this->addTax = null;

        return $cartDataModule->mergeCarts($sessionCart, $storedCart);
    }

    /**
     * @param Cart $cart
     * @param UserInterface|null $user
     * @return mixed
     */
    protected function convertSessionCartToUserCart(Cart $cart, UserInterface $user = null)
    {
        /**
         * @var BaseCartDataModule $cartDataModule
         */
        $cartDataModule = $this->moduleManager->getModuleByName(EdiCartDataModule::NAME);

        return $content = $cartDataModule->convertSessionCartToUserCart($cart, $user);
    }

    /**
     * @param Product $product
     * @param float $quantity
     * @return array
     * @throws \Exception
     */
    public function calculateQuantityForProduct(Product $product, float $quantity)
    {
        /**
         * @var BaseCartDataModule $cartDataModule
         */
        $cartDataModule = $this->moduleManager->getModuleByName(EdiCartDataModule::NAME);
        //Aktualnie użytkowanie metody wyliczającej dostępny stan nie jest niezbędne, z uwagi na pracę triggera
        //$content = $cartDataModule->calculateQuantityForProduct($product, $quantity);
        return $cartDataModule->getCalculatedQuantityForProduct($product, $quantity);
    }

    /**
     * Zwraca informacje o jednej pozycji koszyka w formie tablicy
     * @deprecated
     */
    protected function processCartItemToArray(CartItem $cartItem)
    {
        $cartDataToArrayModule = $this->moduleManager->getModuleByName(BaseCartItemsModule::NAME);
        $content = $cartDataToArrayModule->processCartItemToArray($cartItem);

        return $content;
    }

    /**
     * Zwraca tablicę z informacjami o koszyku
     */
    public function processCartToArray(Cart $cart = null)
    {
        if (!$cart) {
            $cart = $this->getCart();
        }

        $cartDataToArrayModule = $this->moduleManager->getModuleByName(BaseCartDataModule::NAME);

        return $cartDataToArrayModule->processCartToArray($cart);
    }

    /**
     * @param Cart|null $cart
     * @return mixed
     * @throws \Exception
     * @depracted
     */
    public function processPackagesToArray(Cart $cart = null)
    {
        if (!$cart) {
            $cart = $this->getCart();
        }

        $packageShippingModule = $this->moduleManager->getModuleByName(BasePackageShippingModule::NAME);

        return $packageShippingModule->processPackagesToArray($cart);
    }

    /**
     * @return string
     * @deprecated
     */
    public function getLocalOfficeShippingCostMessage()
    {
        return $this->translator->trans('Cart.Label.LocalOfficePricing', [], 'Cart');
    }

    /**
     * @param Cart $cart
     * @return bool
     * @throws \Exception
     */
    public function showVatViesWarning(Cart $cart): bool
    {
        $cartDataModule = $this->moduleManager->getModuleByName(BaseCartDataModule::NAME);
        return $cartDataModule->showVatViesWarning($cart);
    }

    /**
     * Przekazujemy tablicę
     * [
     *  $productId => $quantity
     * ]
     *
     * Dodaje lub uaktualnia pozycję w koszyku na podstawie productId i ilości
     * $updateAllCartItems - argument pozwala wymusić przeliczenie wszystkich pozycji w koszyku, jeżeli występuje zależność pomiędzy zmianą jeden pozycji, a pozostałymi
     * Przeliczanie wszystkich pozycji należy stosować wyłącznie przy obsłudzę zmian pozycji z poziomu widoku kroku 1 koszyka
     * Dodając lub zwiększając ilość dla danej pozycji z poziomu widoku produktu nie ma potrzeby przeliczania pozostałych pozycji
     * @param Cart $cart
     * @param array $updateData
     * @param bool $increaseQuantity
     * @param bool $updateAllCartItems
     * @param bool $fromOrderTemplate
     * @param bool $merge
     * @return array
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function updateCartItems(
        Cart  $cart,
        array $updateData,
        bool  $increaseQuantity = false,
        bool  $updateAllCartItems = false,
        bool  $fromOrderTemplate = false,
        bool  $merge = false
    ): array {
        /**
         * @var BaseCartItemsModule $cartItemModule
         */
        $cartItemModule = $this->moduleManager->getModuleByName(BaseCartItemsModule::NAME);
        $content = $cartItemModule->updateCartItems($cart, $updateData, $increaseQuantity, $updateAllCartItems, $fromOrderTemplate, $merge);
        $cartItemModule->validateDependencies($cart);

        return $content;
    }

    /**
     * @param Cart $cart
     * @param OrderTemplate $orderTemplate
     * @param bool $clearCart
     * @param array $updateData
     * @return array
     */
    public function useOrderTemplate(Cart $cart, OrderTemplate $orderTemplate, bool $clearCart, array $updateData = []): array
    {
        $cart->setOrderTemplate($orderTemplate);

        try {
            if ($clearCart || $orderTemplate->getType() === OrderTemplate::TYPE_CLOSED) {
                //Użycie zamkniętego szablonu spowoduje usunięcie wcześniej dodanych pozycji i wybranych miejsc dostaw
                $this->clearCart($cart);
            }

            //Wprowadzenie pozycji
            $result = $this->updateCartItems($cart, $updateData, false, false, true);

            if ($orderTemplate->getType() === OrderTemplate::TYPE_CLOSED) {
                //Z uwagi na EDI2-193 wyłączamy blokowanie koszyka po dodaniu szablonu zamkniętego, idea szablonu przestaje istnieć
                //Aktualnie to kolejna pula produktów
                //$cart->setIsLocked(true);
            }

            //Aktualizacja miejsc dostaw
            //Jezeli szablon ma podpięte miejsca dostawy, podpinamy je w koszyku
            if ($orderTemplate->getOrderTemplateCustomerDeliveries()->count()) {
                //Uruchamiamy moduł delivery
                /** @var BaseDeliveryDataModule $deliveryDataModule */
                $deliveryDataModule = $this->moduleManager->getModuleByName(BaseDeliveryDataModule::NAME);
                $deliveryDataModule->setDefaultCustomerDeliveries($cart, $orderTemplate->getCustomerDeliveriesWithUuidKeys());
            }

            $this->em->flush();
        } catch (\Exception $e) {
            $result = [];
        }

        return $result;
    }

    /**
     * Przebudowa koszyka, przeliczenie wartości koszyka i paczek.
     *
     * Tutaj powinny znaleźć się wszystkie przeliczenia koszyka (o ile ich wartość jest zapisywana w bazie)
     * Unikamy tego, ale ilość w cart items i paczki są zapisywane, trzeba je aktualizować przy każdym etapie koszyka
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
        $removedUnavailableProducts = $this->removeUnavailableProducts($cart);

        //Pobieramy wartość koszyka i ustalamy wartości pozycji
        //Do weryfikacji czy tutaj powinno się to odbywać każdorazowo
        if ($getCartSummary) {
            $this->getCartSummary($cart, true);
        }


        $cartItems = $cart->getItems();
        $this->storageManager->clearReservedQuantityArray();

        //odswieżamy notyfikacje i aktualizujemy pozycje w koszyku (np. nastąpiła zmiana stanu mag.)
        $notifications = [];

        /**
         * @var CartItem $cartItem
         */
        foreach ($cartItems as $cartItem) {
            $this->checkQuantityAndPriceForCartItem($cartItem, $notifications);

            if ($cartItem->getId() === null) {
                $cartItemRemoved = true;
            }
        }

        //sprawdzenie domyślnego typu podziału paczek
        $this->checkForDefaultCartOverSaleType($cart);

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

    /**
     * Usuwa produkty niedostępne dla płatnika/użytkownika
     *
     * @param Cart $cart
     * @return bool
     * @throws \Exception
     */
    protected function removeUnavailableProducts(Cart $cart): bool
    {
        /**
         * @var BaseCartItemsModule $cartItemsModule
         */
        $cartItemsModule = $this->moduleManager->getModuleByName(BaseCartItemsModule::NAME);
        return $cartItemsModule->removeUnavailableProducts($cart);
    }

    /**
     * Nie używać!
     *
     * @param Cart $cart
     * @throws \Exception
     * @see \LSB\CartBundle\Module\BaseCartItemsModule::removeProductsWithNullPrice()
     * @deprecated Weryfikacja zerowej oceny realizowana wspólnie z weryfikacją stanu mag.
     */
    protected function removeProductsWithNullPrice(Cart $cart): void
    {
        /**
         * @var BaseCartItemsModule $cartItemsModule
         */
        $cartItemsModule = $this->moduleManager->getModuleByName(BaseCartItemsModule::NAME);
        $cartItemsModule->removeProductsWithNullPrice($cart);
    }

    /**
     * Metoda anuluje zapisaną wcześnie walidację w sytuacji przebudowy zawartości paczek
     * Rozważyć dodanie komunikatu flash w takiej sytuacji
     *
     * @param Cart $cart
     */
    public function clearValidatedStep(Cart $cart): void
    {
        $cart->setValidatedStep(null);
    }

    /**
     * @param $cart
     * @return mixed
     * @throws \Exception
     * @see \LSB\CartBundle\Module\BasePackageSplitModule::checkForDefaultCartOverSaleType()
     */
    public function checkForDefaultCartOverSaleType($cart): bool
    {
        /** @var BasePackageSplitModule $packageSplitModule */
        $packageSplitModule = $this->moduleManager->getModuleByName(BasePackageSplitModule::NAME);
        return $packageSplitModule->checkForDefaultCartOverSaleType($cart);
    }

    /**
     * Ustawia adres powrotu do zakupów
     *
     * @depracted
     * @param $backUrl
     */
    public function setBackUrl($backUrl): void
    {
        if ($backUrl) {
            $this->session->set('cartBackUrl', $backUrl);
        }
    }

    /**
     * Pobiera adresu powrotu do zakupoów
     *
     * @depracted
     * @return null|string
     */
    public function getBackUrl(): ?string
    {
        return $this->session->get('cartBackUrl', null);
    }

    /**
     * Czyści flagę scalenia na wszystkich paczkach koszyka
     * Flaga scalenia wyświetlana jest tylko
     *
     * @param Cart $cart
     * @return Cart
     */
    public function clearMergeFlag(Cart $cart): Cart
    {
        foreach ($cart->getPackages() as $package) {
            $package->setIsMerged(false);
            $this->em->persist($package);
        }

        return $cart;
    }

    /**
     * Sprawdzamy dostępność pozycji z koszyka
     *
     * @param CartItem $cartItem
     * @param array $notifications
     * @return CartItem
     * @throws \Exception
     */
    public function checkQuantityAndPriceForCartItem(CartItem $cartItem, array &$notifications): CartItem
    {
        /**
         * @var BaseCartItemsModule $cartItemsModule
         */
        $cartItemsModule = $this->moduleManager->getModuleByName(BaseCartItemsModule::NAME);
        return $cartItemsModule->checkQuantityAndPriceForCartItem($cartItem, $notifications);
    }

    /**
     * @param User $user
     * @param bool $flush
     * @throws \Exception
     * @deprecated
     */
    public function closeOpenCartsForHiddenUser(User $user, bool $flush = true): void
    {
        $currentCartId = $this->getCart()->getId();

        if ($user->getIsHiddenUser() || !$user->isEnabled()) {
            $carts = $this->cartManager->getRepository()->getOpenCartsByUser($user->getId());

            foreach ($carts as $cart) {
                //Zamykamy wszystkie pozostałe otwarte koszyki, za wyjątkiem aktualnie używanego!
                if ($currentCartId == $cart->getId()) {
                    continue;
                }

                $cart->setValidatedStep(CartInterface::CART_STEP_CLOSED_MANUALLY);
                $this->eventDispatcher->dispatch(new CartEvent($cart), CartEvents::CART_CLOSED);
            }
            if ($flush) {
                $this->em->flush();
            }
        }
    }

    /**
     * Ręczne zamknięcie koszyka
     *
     * @param Cart $cart
     * @param bool $flush
     */
    public function closeCart(Cart $cart, bool $flush = true): void
    {
        if ($cart->getValidatedStep() < CartInterface::CART_STEP_ORDER_CREATED) {
            $cart->setValidatedStep(CartInterface::CART_STEP_CLOSED_MANUALLY);
            $this->eventDispatcher->dispatch(new CartEvent($cart), CartEvents::CART_CLOSED);

            if ($flush) {
                $this->em->flush();
            }
        }
    }

    /**
     * @param false $flush
     * @param bool|null $directMarketing
     * @param bool|null $newsletter
     * @return ContractorInterface|null
     * @throws \Exception
     */
    public function updateContractorAgreementsFromCart(
        bool  $flush = false,
        ?bool $directMarketing = null,
        ?bool $newsletter = null
    ): ?ContractorInterface {
        $customer = $this->getUser() ? $this->getUser()->getDefaultCustomer() : null;

        if (!($customer instanceof ContractorInterface)) {
            return null;
        }

//        if ($directMarketing !== null) {
//            $customer->setDirectMarketingAgree($directMarketing);
//        }
//
//        if ($newsletter !== null) {
//            $customer->setNewsletterAgree($newsletter);
//        }

        $this->em->persist($customer);

        if ($flush) {
            $this->em->flush($customer);
        }

        return $customer;
    }

    /**
     * @param CartInterface|null $cart
     * @param bool $forceReload
     * @param bool $onlySelected
     * @return iterable
     * @throws \Exception
     * @deprecated
     */
    public function getCartItems(
        ?CartInterface $cart = null,
        bool           $forceReload = false,
        bool           $onlySelected = false
    ): iterable {
        if (!$cart) {
            $cart = $this->getCart();
        }

        if ($onlySelected) {
            return $cart->getSelectedCartItems();
        }

        return $cart->getCartItems();
    }

    /**
     * @param array $productIds
     * @param Cart|null $cart
     * @return mixed
     * @throws \Exception
     * @deprecated
     */
    public function getSelectedCartItems(
        array $productIds,
        ?Cart $cart = null
    ): array {
        if (!$cart) {
            $cart = $this->getCart();
        }

        $selectedCartItems = $this->em->getRepository(CartItem::class)->getCartItemsByCartAndProduct(
            $cart->getId(),
            $productIds
        );

        return $selectedCartItems;
    }

    /**
     * Metoda określa czy VAT powinien zostać naliczany dla koszyka
     *
     * @param Cart $cart
     * @return null|bool
     * @throws \Exception
     */
    public function addTax(Cart $cart): ?bool
    {
        if ($this->addTax === null) {
            $this->addTax = $this->taxManager->addVat(
                $cart->getCustomerCountry(),
                $cart->getCustomerType(),
                $cart->getCustomerTaxNumber(),
                $cart->getIsCustomerVatUeActive()
            );
        }

        return $this->addTax;
    }

    /**
     * Wylicza wartości dla podsumowania koszyka
     * Podsumowanie koszyka liczone jest tylko dla zaznaczonych pozycji koszyka
     * Flaga przebudowy zawartości koszyka powinna być aktywna tylko w przypadku gdy koszyk zawiera jakieś pozycje.
     * W przypadku koszyków pustych jest to zbędne.
     *
     * @param Cart|null $cart
     * @param bool $injectCartItemsSummary
     * @param bool $rebuildItems
     * @param bool $skipItemsCountCheck
     * @return CartSummary
     * @throws \Exception
     */
    public function getCartSummary(
        Cart $cart = null,
        bool $injectCartItemsSummary = false,
        bool $rebuildItems = false,
        bool $skipItemsCountCheck = true
    ): CartSummary {
        if (!$cart) {
            $cart = $this->getCart();
        }

        $itemsCount = $cart->getItems()->count();

        if ($injectCartItemsSummary && ($itemsCount > 0 || $skipItemsCountCheck)) {
            /**
             * @var BaseCartItemsModule $cartItemsModule
             */
            $cartItemsModule = $this->moduleManager->getModuleByName(BaseCartItemsModule::NAME);
            $cartItemsModule->injectPricesToCartItems($cart);
        }

        /**
         * @var BaseCartDataModule $cartDataModule
         */
        $cartDataModule = $this->moduleManager->getModuleByName(BaseCartDataModule::NAME);
        return $cartDataModule->getCartSummary($cart, $rebuildItems && ($itemsCount > 0 || $skipItemsCountCheck));
    }

    /**
     * Oblicza cenę dostawy dla paczek, bazując na wybranym sposobie dostawy, wartości zamówienia i progu darmowej wysyłki
     *
     * @param Cart|null $cart
     * @param bool $addVat
     * @param mixed $calculatedTotalProductsNetto
     * @param $shippingCostRes
     * @return array
     * @throws \Exception
     * @deprecated
     */
    public function calculateShippingCost(
        Cart $cart = null,
        bool $addVat,
             $calculatedTotalProductsNetto,
             &$shippingCostRes
    ) {
        /**
         * @var BaseCartDataModule $cartDataModule
         */
        $cartDataModule = $this->moduleManager->getModuleByName(BaseCartDataModule::NAME);

        return $cartDataModule->calculateShippingCost(
            $cart,
            $addVat,
            $calculatedTotalProductsNetto,
            $shippingCostRes
        );
    }

    /**
     * Wyliczanie kosztów dostawy z uwzględnieniem dodatkowej opłaty związanej z miejscem dostawy
     *
     * @param Cart|null $cart
     * @param bool $addVat
     * @param array $shippingCostRes
     * @return array
     * @throws \Exception
     * @deprecated
     */
    public function calculateDeliveryCost(Cart $cart, bool $addVat, array &$shippingCostRes): array
    {
        $packages = $cart->getPackages();

        $totalNetto = (float)0.00;
        $totalGrossRounded = (float)0.00;
        $totalGross = (float)0.00;

        foreach ($packages as $package) {
            if ($package->getCustomerDelivery() && $package->getCustomerDelivery()->getTransportCharge()) {
                $tax = $package->getCustomerDelivery()->getTaxRelation() ? $package->getCustomerDelivery()->getTaxRelation()->getValue() : $this->ps->getParameter('default.tax');

                //naliczamy ekstra należność za dostawę dla tego miejsca
                $transportCost = $package->getCustomerDelivery()->getTransportCost();
                [$transportCost] = $this->priceListManager->calculatePrice(
                    $cart->getCurrencyRelation()->getCodeTitle(),
                    $transportCost,
                    1,
                    new \DateTime('now')
                );

                if ($package->getCustomerDelivery()->getPriceType() === PriceTypeInterface::PRICE_TYPE_NETTO) {
                    $transportCostNetto = $transportCost;
                    $transportCostGross = $addVat ? PriceManager::calculateGrossValue(
                        $transportCost,
                        $tax
                    ) : $transportCost;
                } else {
                    $transportCostGross = $transportCost;
                    $transportCostNetto = PriceManager::calculateNettoValue($transportCost, $tax);
                }

                $totalNetto += $transportCostNetto;
                $totalGross += $transportCostGross;
                $totalGrossRounded += $transportCostGross; //@depracted :)

                //Uzupełniamy tablicę netto o koszty dostawy
                TaxManager::addValueToNettoRes($tax, $transportCost, $shippingCostRes);
            }
        }

        return [$totalNetto, $totalGrossRounded, $totalGross];
    }

    /**
     * @return UserBuyerInterface|null
     * @throws \Exception
     */
    protected function getUser(): ?UserBuyerInterface
    {
        if ($this->tokenStorage && $this->tokenStorage->getToken() && $this->tokenStorage->getToken()->getUser() instanceof UserBuyerInterface) {
            $user = $this->tokenStorage->getToken()->getUser();

            if (!$user && !$this->ps->getParameter('cart.notLogged.enabled')) {
                throw new \Exception('User not logged in.');
            }

            return $user;
        }

        return null;
    }

    /**
     * @param Cart $cart
     * @param bool $clearCustomerData
     * @return int
     * @throws \Exception
     */
    public function clearCart(Cart $cart, bool $clearCustomerData = true): int
    {
        $removedItemsCnt = $cart->getItems()->count();

        $cart
            ->clearCart($clearCustomerData)
            ->unlockCart();

        $this->em->flush();

        $this->eventDispatcher->dispatch(
            CartEvents::CART_CLEARED,
            new CartEvent($cart, $this->applicationManager->getApplication())
        );

        return $removedItemsCnt;
    }

    /**
     * Pobiera id sesji koszyka wygenerowane z poziomiu frontu
     *
     * @return string
     * @throws \Exception
     */
    public function getCartSessionId(): string
    {
        $request = $this->requestStack->getMasterRequest();
        $cartSessionId = $request->headers->get(self::CART_SESSION_ID_HEADER_KEY);

        if (!$cartSessionId) {
            //generuje nowe ID sesji koszyka, na podstawie danych lokalnych
            $cartSessionId = sha1(session_id() . microtime());
        }

        //Sprawdzamy poprawność sesji
        //Długość znaków musi wynosić dokładnie 40 znaków

        if ($cartSessionId !== null && !$this->checkCartSHAId($cartSessionId)) {
            throw new \Exception('Wrong cart session id. SHA1 hash is required.');
        }

        $this->setSessionId($cartSessionId);

        return $cartSessionId;
    }

    /**
     * Pobiera id sesji koszyka wygenerowane z poziomiu frontu
     *
     * @return string
     * @throws \Exception
     */
    public function getCartTransactionId(): string
    {
        $request = $this->requestStack->getMasterRequest();
        $cartTransactionId = $request->headers->get(self::CART_TRANSACTION_ID_HEADER_KEY);

        if (!$cartTransactionId) {
            //generuje nowe ID sesji koszyka, na podstawie danych lokalnych
            $cartTransactionId = sha1(session_id() . microtime());
        }

        //Sprawdzamy poprawność sesji
        //Długość znaków musi wynosić dokładnie 40 znaków

        if ($cartTransactionId !== null && !$this->checkCartSHAId($cartTransactionId)) {
            throw new \Exception('Wrong cart session id. SHA1 hash is required.');
        }

        $this->setTransactionId($cartTransactionId);

        return $cartTransactionId;
    }

    /**
     * @param string|null $cartSessionId
     * @return bool
     */
    protected function checkCartSHAId(?string $cartSessionId): bool
    {
        return (bool)preg_match('/^[0-9a-f]{40}$/i', $cartSessionId);
    }


    /**
     * @param string $cartSessionId
     */
    public function setSessionId(string $cartSessionId): void
    {
        $this->session->set(self::CART_SESSION_ID_ATTR_NAME, $cartSessionId);
    }

    /**
     * @param string $cartTransactionId
     */
    public function setTransactionId(string $cartTransactionId): void
    {
        $this->session->set(self::CART_TRANSACTION_ID_ATTR_NAME, $cartTransactionId);
    }

    /**
     * Czyści ID sesji użytkownika
     */
    public function clearSessionId(): void
    {
        $this->session->set(self::CART_SESSION_ID_ATTR_NAME, null);
    }


    /**
     * Wpisanie aktualnych cen dla pozycji w koszyku
     * Korzysta z modułu cartItems
     *
     * @param Cart $cart
     * @param bool $setActivePrice
     * @throws \Exception
     * @deprecated Zastępować pobierając kompletne CartSummary
     */
    public function injectPricesToCartItems(Cart $cart, bool $setActivePrice = true): void
    {
        /**
         * @var BaseCartItemsModule $cartItemsModule
         */
        $cartItemsModule = $this->loadModule(BaseCartItemsModule::NAME);
        $cartItemsModule->injectPricesToCartItems($cart, $setActivePrice);
    }

    /**
     * @param $moduleName
     * @return CartModuleInterface
     * @throws \Exception
     */
    protected function loadModule($moduleName): CartModuleInterface
    {
        $cartItemsModule = $this->moduleManager->getModuleByName($moduleName);
        if (!($cartItemsModule instanceof CartModuleInterface)) {
            throw new \Exception('Missing module ' . $moduleName);
        }

        return $cartItemsModule;
    }

    /**
     * @param Cart|null $cart
     * @return CartSummary
     * @throws \Exception
     * @deprecated Zastępować pobierając kompletne CartSummary
     */
    public function injectPricesToCart(Cart $cart = null): CartSummary
    {
        if (!$cart) {
            $cart = $this->getCart();
        }

        /**
         * @var BaseCartDataModule $cartDataModule
         */
        $cartDataModule = $this->moduleManager->getModuleByName(EdiCartDataModule::NAME);
        return $cartDataModule->getCartSummary($cart);
    }

    /**
     * @param float $price
     * @param int $quantity
     * @param bool $round
     * @param int $precision
     * @return float
     */
    public function calculateNettoValue(float $price, int $quantity, bool $round = true, int $precision = 2): float
    {
        $value = round($price, $precision) * $quantity;

        return $round ? round($value, $precision) : $value;
    }

    /**
     * @param float $price
     * @param int $quantity
     * @param int|null $taxPercentage
     * @param bool $round
     * @param int $precision
     * @return float
     */
    public function calculateGrossValueFromNetto(
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
     * @param float $price
     * @param int $quantity
     * @param int|null $taxPercentage
     * @param bool $round
     * @param int $precision
     * @return float
     */
    public function calculateNettoValueFromGross(
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
     * @param bool|null $value
     */
    public function setSessionRulesAccepted(?bool $value): void
    {
        $this->session->set(self::CART_SESSION_RULES_ACCEPTED_ATTR_NAME, (bool)$value);
    }

    /**
     * @return bool
     */
    public function getSessionRulesAccepted(): bool
    {
        return $this->session->get(self::CART_SESSION_RULES_ACCEPTED_ATTR_NAME, false);
    }

    /**
     * @return bool
     */
    public function isBackorderEnabled(): bool
    {
        //zakładamy, że tablica zawiera niezbędne dane
        if ($this->ps->getParameter('order.bundle')['backOrder']['enabled']) {
            return true;
        }

        return false;
    }

    /**
     * Aktualizacje paczki koszyka
     * W oparciu o dane cartItems wylicza zawartość paczek
     *
     * @param Cart $cart
     * @param bool $splitSupplier
     * @return bool|null
     * @throws \Exception
     */
    public function updatePackages(Cart $cart, bool $splitSupplier = false): ?bool
    {
        /**
         * @var BasePackageSplitModule $packageSplitModule
         */
        $packageSplitModule = $this->moduleManager->getModuleByName(BasePackageSplitModule::NAME);
        return $packageSplitModule->updatePackages($cart, $splitSupplier);
    }

    /**
     * Rozbicie paczek na dostawców
     *
     * @param Cart $cart
     * @param bool $flush
     * @throws \Exception
     */
    public function splitPackagesForSuppliers(Cart $cart, bool $flush = true): void
    {
        /**
         * @var BasePackageSplitModule $packageSplitModule
         */
        $packageSplitModule = $this->moduleManager->getModuleByName(BasePackageSplitModule::NAME);
        $packageSplitModule->splitPackagesForSupplier($cart, $flush);
    }

    /**
     * @param CartPackage $package
     * @param bool $addVat
     * @param null $calculatedTotalProducts
     * @param null $shippingCostRes
     * @return mixed
     * @throws \Exception
     */
    public function calculatePackageShippingCost(
        CartPackage $package,
                    $addVat = true,
                    $calculatedTotalProducts = null,
                    &$shippingCostRes = null
    ): CartShippingFormCalculatorResult {
        /**
         * @var BasePackageShippingModule $packageShippingModule
         */
        $packageShippingModule = $this->moduleManager->getModuleByName(BasePackageShippingModule::NAME);

        return $packageShippingModule->calculatePackageShippingCost(
            $package,
            $addVat,
            $calculatedTotalProducts,
            $shippingCostRes
        );
    }

    /**
     * @param Cart|null $cart
     * @return bool
     * @throws \Exception
     */
    public function isViewable(?Cart $cart = null)
    {
        if (!$cart) {
            $cart = $this->getCart();
        }

        //Upraszczamy, sprawdzamy ilość pozycji niezależnie od typu pozycji
        //Do rozważenia inna organizacja wyciągania typów
        if (!$cart->getItems()->count()) {
            return false;
        }

        return true;
    }

    /**
     * @param Cart $cart
     */
    public function lockCart(Cart $cart): void
    {
        $cart->setIsLocked(true);
    }

    /**
     * @param Cart $cart
     * @param bool $clearCart
     * @throws \Exception
     */
    public function unlockCart(Cart $cart, bool $clearCart = true): void
    {
        if ($clearCart) {
            $this->clearCart($cart);
        }

        $cart->setIsLocked(false);
    }

    /**
     * @param CartItem $cartItem
     * @return Price|null
     * @throws \Exception
     */
    public function getPriceForCartItem(CartItem $cartItem): ?Price
    {
        /**
         * @var BaseCartItemsModule $module
         */
        $module = $this->moduleManager->getModuleByName(BaseCartItemsModule::NAME);
        return $module->getPriceForCartItem($cartItem);
    }

    /**
     * @return CartRepository
     */
    public function getCartRepository(): CartRepository
    {
        return $this->cartManager->getRepository();
    }

    /**
     * @param Cart $cart
     * @param Request $request
     */
    public function injectCartItemsThumbs(Cart $cart, Request $request): void
    {
        /** @var CartItem $cartItem */
        foreach ($cart->getCartItems() as $cartItem) {
            if ($cartItem->getProduct() instanceof Product) {
                //TODO
            }
        }
    }
}
