<?php
declare(strict_types=1);

namespace LSB\OrderBundle\CartComponent;

use JMS\Serializer\SerializerInterface;
use LSB\ContractorBundle\Entity\ContractorInterface;
use LSB\LocaleBundle\Entity\CurrencyInterface;
use LSB\LocaleBundle\Manager\TaxManager;
use LSB\OrderBundle\Calculator\CartTotalCalculator;
use LSB\OrderBundle\CartHelper\QuantityHelper;
use LSB\OrderBundle\Entity\BillingData;
use LSB\OrderBundle\Entity\Cart;
use LSB\OrderBundle\Entity\CartInterface;
use LSB\OrderBundle\Entity\CartItem;
use LSB\OrderBundle\Entity\CartItemInterface;
use LSB\OrderBundle\Entity\CartPackage;
use LSB\OrderBundle\Event\CartEvent;
use LSB\OrderBundle\Event\CartEvents;
use LSB\OrderBundle\Exception\WrongPackageQuantityException;
use LSB\OrderBundle\Manager\CartItemManager;
use LSB\OrderBundle\Manager\CartManager;
use LSB\OrderBundle\Model\CartSummary;
use LSB\OrderBundle\Service\CartCalculatorService;
use LSB\OrderBundle\Service\CartService;
use LSB\PaymentBundle\Entity\Method as PaymentMethod;
use LSB\PricelistBundle\Manager\PricelistManager;
use LSB\PricelistBundle\Model\Price;
use LSB\PricelistBundle\Service\TotalCalculatorManagerInterface;
use LSB\ProductBundle\Entity\Product;
use LSB\ProductBundle\Entity\ProductSetProduct;
use LSB\ProductBundle\Entity\StorageInterface;
use LSB\ProductBundle\Entity\Supplier;
use LSB\ProductBundle\Manager\ProductManager;
use LSB\ProductBundle\Manager\ProductSetProductManager;
use LSB\ProductBundle\Manager\StorageManager;
use LSB\ProductBundle\Service\StorageService;
use LSB\ShippingBundle\Manager\MethodManager as ShippingMethodManager;
use LSB\UserBundle\Entity\User;
use LSB\UserBundle\Entity\UserInterface;
use LSB\UserBundle\Manager\UserManager;
use LSB\UtilityBundle\Helper\ValueHelper;
use LSB\UtilityBundle\Value\Value;
use Money\Currency as MoneyCurrency;
use Money\Money;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/**
 *
 */
class DataCartComponent extends BaseCartComponent
{
    const NAME = 'data';

    protected CartInterface|null $cart = null;

    public function __construct(
        TokenStorageInterface                     $tokenStorage,
        protected ParameterBagInterface           $ps,
        protected CartManager                     $cartManager,
        protected CartItemManager                 $cartItemManager,
        protected ShippingMethodManager           $shippingFormManager,
        protected pricelistManager                $pricelistManager,
        protected StorageManager                  $storageManager,
        protected StorageService                  $storageService,
        protected RequestStack                    $requestStack,
        protected EventDispatcherInterface        $eventDispatcher,
        protected TaxManager                      $taxManager,
        protected FormFactory                     $formFactory,
        protected UserManager                     $userManager,
        protected SerializerInterface             $serializer,
        protected AuthorizationCheckerInterface   $authorizationChecker,
        protected Environment                     $templating,
        protected TranslatorInterface             $translator,
        protected ProductManager                  $productManager,
        protected ProductSetProductManager        $productSetProductManager,
        protected CartCalculatorService           $cartCalculatorService,
        protected TotalCalculatorManagerInterface $totalCalculatorManager,
        protected QuantityHelper                  $quantityHelper
    ) {
        parent::__construct($tokenStorage);
    }

    /**
     * @return ParameterBagInterface
     */
    public function getPs(): ParameterBagInterface
    {
        return $this->ps;
    }

    /**
     * @return ShippingMethodManager
     */
    public function getShippingFormManager(): ShippingMethodManager
    {
        return $this->shippingFormManager;
    }

    /**
     * @return PricelistManager
     */
    public function getPricelistManager(): PricelistManager
    {
        return $this->pricelistManager;
    }

    /**
     * @return StorageManager
     */
    public function getStorageManager(): StorageManager
    {
        return $this->storageManager;
    }

    /**
     * @return StorageService
     */
    public function getStorageService(): StorageService
    {
        return $this->storageService;
    }

    /**
     * @return RequestStack
     */
    public function getRequestStack(): RequestStack
    {
        return $this->requestStack;
    }

    /**
     * @return EventDispatcherInterface
     */
    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    /**
     * @return TaxManager
     */
    public function getTaxManager(): TaxManager
    {
        return $this->taxManager;
    }

    /**
     * @return FormFactory
     */
    public function getFormFactory(): FormFactory
    {
        return $this->formFactory;
    }

    /**
     * @return UserManager
     */
    public function getUserManager(): UserManager
    {
        return $this->userManager;
    }

    /**
     * @return SerializerInterface
     */
    public function getSerializer(): SerializerInterface
    {
        return $this->serializer;
    }

    /**
     * @return AuthorizationCheckerInterface
     */
    public function getAuthorizationChecker(): AuthorizationCheckerInterface
    {
        return $this->authorizationChecker;
    }

    /**
     * @return Environment
     */
    public function getTemplating(): Environment
    {
        return $this->templating;
    }

    /**
     * @return CartManager
     */
    public function getCartManager(): CartManager
    {
        return $this->cartManager;
    }

    /**
     * @return CartItemManager
     */
    public function getCartItemManager(): CartItemManager
    {
        return $this->cartItemManager;
    }

    /**
     * @return TranslatorInterface
     */
    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    /**
     * @return ProductManager
     */
    public function getProductManager(): ProductManager
    {
        return $this->productManager;
    }

    /**
     * @return ProductSetProductManager
     */
    public function getProductSetProductManager(): ProductSetProductManager
    {
        return $this->productSetProductManager;
    }

    /**
     * @return CartCalculatorService
     */
    public function getCartCalculatorService(): CartCalculatorService
    {
        return $this->cartCalculatorService;
    }

    /**
     * @return TotalCalculatorManagerInterface
     */
    public function getTotalCalculatorManager(): TotalCalculatorManagerInterface
    {
        return $this->totalCalculatorManager;
    }

    /**
     * TODO
     * @return mixed
     * @deprecated Moved to CartDataService
     * #
     */
    public function getDefaultDetalPriceType()
    {
        return null;
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
            $cart->getBillingContractor()
        );
    }

    /**
     * @param CartItem $selectedCartItem
     * @return Price
     * @throws \Exception
     * @deprecated
     */
    public function getActivePriceForCartItem(CartItemInterface $selectedCartItem): Price
    {
        $price = $this->getPriceForCartItem($selectedCartItem);

        if (!$price instanceof Price) {
            throw new \Exception('Missing price object');
        }

        return $price;
    }

    /**
     * Metoda zwraca minimalny koszt dostawy dostępny dla klienta
     * TODO
     *
     * @param Cart $cart
     * @param Money $shippingCostNetto
     * @return array
     */
    public function getShippingCostFrom(CartInterface $cart, Money $shippingCostNetto): array
    {
        //TODO calculate shippingCostFrom
        return [
            new Money(0, new MoneyCurrency($cart->getCurrencyIsoCode())),
            new Money(0, new MoneyCurrency($cart->getCurrencyIsoCode()))
        ];
    }

    /**
     * @param Cart $cart
     * @return bool
     * @throws \Exception
     */
    public function showVatViesWarning(Cart $cart): bool
    {
        if ($cart->getBillingContractorCountry()
            && $this->taxManager->getSellerCountry() !== $cart->getBillingContractorCountry()
            && $cart->getBillingContractorCountry()->isUeMember()
            && (
                $cart->getBillingContractorData()->getType() === BillingData::TYPE_COMPANY
                || $cart->getBillingContractorData()->getType() === null
            ) && !$cart->getBillingContractorData()->getIsVatUEActivePayer()
        ) {
            return true;
        }

        return false;
    }

    /**
     * @param Cart $cart
     * @throws \Exception
     */
    public function prepare(CartInterface $cart)
    {
        $user = $this->getUser();

        if ($user && $user->getDefaultBillingContractor() && $cart->getBillingContractorData()->getEmail() === null) {
            $cart->getBillingContractorData()->setEmail($user->getEmail());
        }

        return;
    }

    /**
     * @inheritdoc
     */
    public function render(CartInterface $cart, ?Request $request = null, bool $isInitialRender = false)
    {
        return null;
    }

    /**
     * @param Cart $cart
     * @param UserInterface|null $user
     */
    public function convertSessionCartToUserCart(Cart $cart, UserInterface $user = null)
    {
        $customer = $user->getDefaultBillingContractor();

        $cart
            ->setAuthType(CartInterface::AUTH_TYPE_USER)
            ->setUser($user)
            ->setBillingContractor($customer)
            ->setIsConvertedFromSession(true)
            ->setSessionId(null)
            ->setValidatedStep(null);

        //TODO
        //CartPackage validation is reuqired at this moment
//        $packageShippingModule = $this->moduleManager->getModuleByName(BasePackageShippingModule::NAME);
//        if ($packageShippingModule) {
//            $packageShippingModule->validate($cart);
//        }

        //Payment validation is required at this moment
//        $paymentModule = $this->moduleManager->getModuleByName(BasePaymentModule::NAME);
//        if ($paymentModule) {
//            $paymentModule->validate($cart);
//        }

        $this->eventDispatcher->dispatch(
            new CartEvent($cart),
            CartEvents::CART_SESSION_CONVERTED_TO_USER,
        );

        $this->cartManager->flush();
    }

    /**
     * Metoda scalająca pozycje w koszyku, wymusza aktualizacją paczek
     *
     * @param Cart $sessionCart
     * @param Cart $storedCart
     * @return Cart
     * @throws \Exception
     */
    public function mergeCarts(Cart $sessionCart, Cart $storedCart): Cart
    {
        //w takiej sytuacji zakładamy, że koszyk sesyjny zostanie zamknięty

        //budujemy tablicę aktualizacji
        $updateData = [];

        /**
         * @var CartItem $sessionCartItem
         */
        foreach ($sessionCart->getCartItems() as $sessionCartItem) {
            if (!$sessionCartItem->getProduct()) {
                continue;
            }
            //TODO zamienić na value objects
            $updateData[] = [
                'uuid' => $sessionCartItem->getProduct()->getUuid(),
                'quantity' => $sessionCartItem->getQuantity(),
                'ordercode' => $sessionCartItem->getOrdercode(),
            ];
        }


        /**
         * @var CartItemsModule $cartItemsModule
         */
//        $cartItemsModule = $this->moduleManager->getModuleByName(BaseCartItemsModule::NAME);
//
//        $processedItemsData = $cartItemsModule->rebuildAndProcessCartItems($storedCart);
//
//        if (count($updateData) > 0) {
//            //rozpoczynamy proces scalania
//            $cartItemsModule->updateCartItems($storedCart, $updateData, true, true, false, true);
//        }

        //Zamykamy koszyk sesyjny
        $sessionCartValidatedStep = $sessionCart->getValidatedStep();
        $sessionCart->setValidatedStep(Cart::CART_STEP_CLOSED_BY_MERGE);

        $this->eventDispatcher->dispatch(
            new CartEvent(
                $sessionCart
            ),
            CartEvents::CART_CLOSED
        );

        //Zapisujemy koszyk permanentny
        $storedCart
            ->setValidatedStep(null)
            //->clearPackagesDefaultDeliveryData()
            ->getBillingContractorData()->setIsVatUEActivePayer(null);
        $storedCart
            ->setIsMerged(true)
            ->setNote($sessionCart->getNote() ? $storedCart->getNote() . "\n" . $sessionCart->getNote() : $storedCart->getNote())
            ->setInvoiceNote($sessionCart->getInvoiceNote() ? $storedCart->getInvoiceNote() . "\n" . $sessionCart->getInvoiceNote() : $storedCart->getInvoiceNote())
            ->setOrderVerificationNote($sessionCart->getOrderVerificationNote() ? $storedCart->getOrderVerificationNote() . "\n" . $sessionCart->getOrderVerificationNote() : $storedCart->getOrderVerificationNote());

        if ($sessionCart->isOrderVerificationRequested()) {
            $storedCart->setIsOrderVerificationRequested($sessionCart->isOrderVerificationRequested());
        }

        if ($sessionCart->getClientOrderNumber()) {
            $storedCart->setClientOrderNumber($sessionCart->getClientOrderNumber());
        }

        if ($sessionCart->getPaymentMethod()) {
            $storedCart->setPaymentMethod($sessionCart->getPaymentMethod());
        }

        if ($sessionCart->getAuthType()) {
            //Aktualnie zawsze ustawiony zostanie type USER
            $storedCart->setAuthType($sessionCart->getAuthType());
        }

        if ($sessionCart->getBillingContractorCountry()) {
            $storedCart->setBillingContractorCountry($sessionCart->getBillingContractorCountry());
        }

        //Przepisanie ustawień paczek
        $sessionPackages = [];

        /**
         * @var CartPackage $sessionPackage
         */
        foreach ($sessionCart->getCartPackages() as $key => $sessionPackage) {
            $sessionPackages[$key] = $sessionPackage;
        }

        /**
         * @var CartPackage $storedPackage
         */
        foreach ($storedCart->getCartPackages() as $key => $storedPackage) {
            if (array_key_exists($key, $sessionPackages)) {
                $storedPackage
                    ->setDeliveryNote($sessionPackages[$key]->getDeliveryNotes() ? $storedPackage->getDeliveryNote() . "\n" . $sessionPackages[$key]->getDeliveryNotes() : $storedPackage->getDeliveryNote());

                if ($sessionPackages[$key]->getShippingMethod()) {
                    $storedPackage->setShippingMethod($sessionPackages[$key]->getShippingMethod());
                }
            }
        }

        $this->eventDispatcher->dispatch(
            new CartEvent(
                $storedCart
            ),
            CartEvents::CART_MERGED
        );

        $this->cartManager->persist($storedCart);
        $this->cartManager->flush();

        return $storedCart;
    }

    /**
     * @param bool|null $forceReload
     * @param UserInterface|null $user
     * @param ContractorInterface|null $contractor
     * @param bool $requireCreate
     * @param string|null $cartSessionId
     * @param string|null $cartTransactionId
     * @return Cart|null
     * @throws \Exception
     */
    public function getCart(
        ?bool                $forceReload = false,
        ?UserInterface       $user = null,
        ?ContractorInterface $contractor = null,
        bool                 $requireCreate = true,
        ?string              $cartSessionId = null,
        ?string              $cartTransactionId = null
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
            $sessionId = $this->getCartSessionId($cartSessionId);
            $transactionId = $this->getCartTransactionId($cartTransactionId);
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
            $this->cartManager->flush();
        }

        return $this->cart;
    }

    /**
     * @param string $oldSessionId
     * @return CartInterface|null
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
        if (!$cart->getCartItems()->count()) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    public function isBackorderEnabled(): bool
    {
        //TODO fixed
        return true;

        //zakładamy, że tablica zawiera niezbędne dane
        if ($this->ps->get('order.bundle')['backOrder']['enabled']) {
            return true;
        }

        return false;
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
     * @param bool|null $value
     */
    public function setSessionRulesAccepted(?bool $value): void
    {
        $this->requestStack->getSession()->set(CartService::CART_SESSION_RULES_ACCEPTED_ATTR_NAME, (bool)$value);
    }

    /**
     * @return bool
     */
    public function getSessionRulesAccepted(): bool
    {
        return $this->requestStack->getSession()->get(CartService::CART_SESSION_RULES_ACCEPTED_ATTR_NAME, false);
    }

    /**
     * Pobiera id sesji koszyka wygenerowane z poziomiu frontu
     *
     * @param string|null $cartTransactionId
     * @return string
     * @throws \Exception
     */
    public function getCartTransactionId(?string $cartTransactionId = null): string
    {
        if (!$cartTransactionId) {
            $request = $this->requestStack->getMasterRequest();
            $cartTransactionId = $request->headers->get(CartService::CART_TRANSACTION_ID_HEADER_KEY);
        }

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
        //$this->session->set(self::CART_SESSION_ID_ATTR_NAME, $cartSessionId);
    }

    /**
     * @param string $cartTransactionId
     */
    public function setTransactionId(string $cartTransactionId): void
    {
        //$this->session->set(self::CART_TRANSACTION_ID_ATTR_NAME, $cartTransactionId);
    }

    /**
     * Czyści ID sesji użytkownika
     */
    public function clearSessionId(): void
    {
        //$this->session->set(self::CART_SESSION_ID_ATTR_NAME, null);
    }

    /**
     * Pobiera id sesji koszyka wygenerowane z poziomiu frontu
     *
     * @param string|null $cartSessionId
     * @return string
     * @throws \Exception
     */
    public function getCartSessionId(?string $cartSessionId = null): string
    {
        //Checks request headers for cart session id
        if (!$cartSessionId) {
            $request = $this->requestStack->getMasterRequest();
            $cartSessionId = $request->headers->get(CartService::CART_SESSION_ID_HEADER_KEY);
        }

        //Generating new cart session id using SHA1
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

        $this->cartManager->flush();

        $this->eventDispatcher->dispatch(
            new CartEvent($cart),
            CartEvents::CART_CLEARED,
        );

        return $removedItemsCnt;
    }

//    /**
//     * Wyliczanie kosztów dostawy z uwzględnieniem dodatkowej opłaty związanej z miejscem dostawy
//     *
//     * @param Cart $cart
//     * @param bool $addVat
//     * @param array $shippingCostRes
//     * @return array
//     * @deprecated
//     * @deprecated
//     */
//    public function calculateDeliveryCost(Cart $cart, bool $addVat, array &$shippingCostRes): array
//    {
//        $packages = $cart->getPackages();
//
//        $totalNetto = (float)0.00;
//        $totalGrossRounded = (float)0.00;
//        $totalGross = (float)0.00;
//
//        foreach ($packages as $package) {
//            if ($package->getCustomerDelivery() && $package->getCustomerDelivery()->getTransportCharge()) {
//                $tax = $package->getCustomerDelivery()->getTaxRelation() ? $package->getCustomerDelivery()->getTaxRelation()->getValue() : $this->ps->getParameter('default.tax');
//
//                //naliczamy ekstra należność za dostawę dla tego miejsca
//                $transportCost = $package->getCustomerDelivery()->getTransportCost();
//                [$transportCost] = $this->priceListManager->calculatePrice(
//                    $cart->getCurrencyRelation()->getCodeTitle(),
//                    $transportCost,
//                    1,
//                    new \DateTime('now')
//                );
//
//                if ($package->getCustomerDelivery()->getPriceType() === PriceTypeInterface::PRICE_TYPE_NETTO) {
//                    $transportCostNetto = $transportCost;
//                    $transportCostGross = $addVat ? PriceManager::calculateGrossValue(
//                        $transportCost,
//                        $tax
//                    ) : $transportCost;
//                } else {
//                    $transportCostGross = $transportCost;
//                    $transportCostNetto = PriceManager::calculateNettoValue($transportCost, $tax);
//                }
//
//                $totalNetto += $transportCostNetto;
//                $totalGross += $transportCostGross;
//                $totalGrossRounded += $transportCostGross; //@depracted :)
//
//                //Uzupełniamy tablicę netto o koszty dostawy
//                TaxManager::addValueToNettoRes($tax, $transportCost, $shippingCostRes);
//            }
//        }
//
//        return [$totalNetto, $totalGrossRounded, $totalGross];
//    }

    /**
     * Metoda określa czy VAT powinien zostać naliczany dla koszyka
     *
     * @param Cart $cart
     * @return null|bool
     * @throws \Exception
     */
    public function addTax(Cart $cart): ?bool
    {
        return $this->taxManager->addVat(
            null,//$cart->getBillingContractor()->getCountry(), TODO add country support
            $cart->getBillingContractor()?->getType() ?? ContractorInterface::TYPE_PRIVATE,
            $cart->getBillingContractor()?->getTaxNumber(),
            (bool)$cart->getVatCalculationType() //TODO fix
        );
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

        return $this->cartManager->getRepository()->getCartItemsByCartAndProduct(
            $cart->getId(),
            $productIds
        );
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
     * @param CartInterface $cart
     * @param bool $flush
     */
    public function closeCart(CartInterface $cart, bool $flush = true): void
    {
        if ($cart->getValidatedStep() < CartInterface::CART_STEP_ORDER_CREATED) {
            $cart->setValidatedStep(CartInterface::CART_STEP_CLOSED_MANUALLY);
            $this->eventDispatcher->dispatch(new CartEvent($cart), CartEvents::CART_CLOSED);

            if ($flush) {
                $this->cartManager->flush();
            }
        }
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

        if (!$user->isEnabled()) {
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
                $this->cartManager->flush();
            }
        }
    }

    /**
     * @param CartInterface $cart
     * @return Cart
     */
    public function clearMergeFlag(CartInterface $cart): CartInterface
    {
        /**
         * @var CartPackage $package
         */
        foreach ($cart->getCartPackages() as $package) {
            $package->setIsMerged(false);
        }

        return $cart;
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


//    public function useOrderTemplate(Cart $cart, OrderTemplate $orderTemplate, bool $clearCart, array $updateData = []): array
//    {
//        $cart->setOrderTemplate($orderTemplate);
//
//        try {
//            if ($clearCart || $orderTemplate->getType() === OrderTemplate::TYPE_CLOSED) {
//                //Użycie zamkniętego szablonu spowoduje usunięcie wcześniej dodanych pozycji i wybranych miejsc dostaw
//                $this->clearCart($cart);
//            }
//
//            //Wprowadzenie pozycji
//            $result = $this->updateCartItems($cart, $updateData, false, false, true);
//
//            if ($orderTemplate->getType() === OrderTemplate::TYPE_CLOSED) {
//                //Z uwagi na EDI2-193 wyłączamy blokowanie koszyka po dodaniu szablonu zamkniętego, idea szablonu przestaje istnieć
//                //Aktualnie to kolejna pula produktów
//                //$cart->setIsLocked(true);
//            }
//
//            //Aktualizacja miejsc dostaw
//            //Jezeli szablon ma podpięte miejsca dostawy, podpinamy je w koszyku
//            if ($orderTemplate->getOrderTemplateCustomerDeliveries()->count()) {
//                //Uruchamiamy moduł delivery
//                /** @var BaseDeliveryDataModule $deliveryDataModule */
//                $deliveryDataModule = $this->moduleManager->getModuleByName(BaseDeliveryDataModule::NAME);
//                $deliveryDataModule->setDefaultCustomerDeliveries($cart, $orderTemplate->getCustomerDeliveriesWithUuidKeys());
//            }
//
//            $this->em->flush();
//        } catch (\Exception $e) {
//            $result = [];
//        }
//
//        return $result;
//    }

    /**
     * @param UserInterface|null $user
     * @param ContractorInterface|null $contractor
     * @param string $sessionId
     * @param string|null $transactionId
     * @return CartInterface
     * @throws \Exception
     */
    protected function createNewCart(
        ?UserInterface       $user,
        ?ContractorInterface $contractor,
        string               $sessionId,
        ?string              $transactionId = null
    ): CartInterface {

        $cart = $this->cartManager->createNew();

        $cart
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

        $this->cartManager->persist($cart);

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
        //return $this->priceListManager->getCurrency($customer);
        return null;
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
}