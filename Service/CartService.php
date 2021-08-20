<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use LSB\ContractorBundle\Entity\Contractor;
use LSB\LocaleBundle\Manager\TaxManager;
use LSB\OrderBundle\CartComponent\DataCartComponent;
use LSB\OrderBundle\CartModule\CartItemCartModule;
use LSB\OrderBundle\CartModule\CartModuleInterface;
use LSB\OrderBundle\Entity\Cart;
use LSB\OrderBundle\Entity\CartInterface;
use LSB\OrderBundle\Entity\CartItem;
use LSB\OrderBundle\Manager\CartManager;
use LSB\OrderBundle\Model\CartSummary;
use LSB\OrderBundle\Module\DataCartModule;
use LSB\OrderBundle\Repository\CartRepository;
use LSB\PricelistBundle\Manager\PricelistManager;
use LSB\ProductBundle\Entity\Product;
use LSB\ProductBundle\Manager\ProductManager;
use LSB\ProductBundle\Manager\StorageManager;
use LSB\UserBundle\Entity\User;
use LSB\UserBundle\Entity\UserInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
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
     * @param ParameterBagInterface $ps
     * @param ProductManager $productManager
     * @param StorageManager $storageManager
     * @param PriceListManager $priceListManager
     * @param TaxManager $taxManager
     * @param CartModuleService $moduleManager
     * @param EventDispatcherInterface $eventDispatcher
     * @param RequestStack $requestStack
     * @param CartComponentService $cartComponentService
     */
    public function __construct(
        protected CartManager              $cartManager,
        protected EntityManagerInterface   $em,
        protected TranslatorInterface      $translator,
        protected TokenStorageInterface    $tokenStorage,
        protected ParameterBagInterface    $ps,
        protected ProductManager           $productManager,
        protected StorageManager           $storageManager,
        protected PriceListManager         $priceListManager,
        protected TaxManager               $taxManager,
        protected CartModuleService        $moduleManager,
        protected EventDispatcherInterface $eventDispatcher,
        protected RequestStack             $requestStack,
        protected CartComponentService     $cartComponentService
    ) {
    }

    /**
     * @param bool|null $forceReload
     * @param User|null $user
     * @param Contractor|null $contractor
     * @param bool $requireCreate
     * @param string|null $cartSessionId
     * @param string|null $cartTransactionId
     * @return Cart|null
     * @throws \Exception
     */
    public function getCart(
        ?bool       $forceReload = false,
        ?User       $user = null,
        ?Contractor $contractor = null,
        bool        $requireCreate = true,
        ?string     $cartSessionId = null,
        ?string     $cartTransactionId = null
    ): ?Cart {
        return $this->cartComponentService->getComponentByClass(DataCartComponent::class)->getCart(
            $forceReload,
            $user,
            $contractor,
            $requireCreate,
            $cartSessionId,
            $cartTransactionId
        );
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
     *
     *
     * @param Cart $sessionCart
     * @param Cart $storedCart
     * @return Cart
     * @throws \Exception
     */
    public function mergeCarts(Cart $sessionCart, Cart $storedCart): Cart
    {

        $cartDataModule = $this->moduleManager->getModuleByName(DataCartModule::NAME);
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
        $cartItemModule = $this->moduleManager->getModuleByName(CartItemCartModule::NAME);
        $content = $cartItemModule->updateCartItems($cart, $updateData, $increaseQuantity, $updateAllCartItems, $fromOrderTemplate, $merge);
        $cartItemModule->validateDependencies($cart);

        return $content;
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
     * @param $cart
     * @return mixed
     * @throws \Exception
     * @see \LSB\CartBundle\Module\BasePackageSplitModule::checkForDefaultCartOverSaleType()
     * @deprecated
     */
    public function checkForDefaultCartOverSaleType($cart): bool
    {
        /** @var BasePackageSplitModule $packageSplitModule */
        $packageSplitModule = $this->moduleManager->getModuleByName(BasePackageSplitModule::NAME);
        return $packageSplitModule->checkForDefaultCartOverSaleType($cart);
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
             * @var CartItemsModule $cartItemsModule
             */
            $cartItemsModule = $this->moduleManager->getModuleByName(BaseCartItemsModule::NAME);
            $cartItemsModule->injectPricesToCartItems($cart);
        }

        /**
         * @var CartDataModule $cartDataModule
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
        $module = $this->moduleManager->getModuleByName($moduleName);
        if (!$module instanceof CartModuleInterface) {
            throw new \Exception('Missing module ' . $moduleName);
        }

        return $module;
    }

    /**
     * @param string $componentName
     * @return CartModuleInterface
     * @throws \Exception
     */
    protected function loadComponent(string $componentName): CartModuleInterface
    {
        $componentModule = $this->cartComponentService->getComponentByClass($componentName);

        if (!$componentModule instanceof CartModuleInterface) {
            throw new \Exception('Missing component ' . $componentName);
        }

        return $componentModule;
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
     * @throws \Exception
     */
    public function lockCart(Cart $cart): void
    {
        $this->cartComponentService->getComponentByClass(DataCartComponent::class)->lockCart($cart);
    }

    /**
     * @param Cart $cart
     * @param bool $clearCart
     * @throws \Exception
     */
    public function unlockCart(Cart $cart, bool $clearCart = true): void
    {
        $this->cartComponentService->getComponentByClass(DataCartComponent::class)->unlockCart($cart, $clearCart);
    }

    /**
     * @param Cart|null $cart
     * @return bool
     * @throws \Exception
     */
    public function isViewable(?Cart $cart = null)
    {
        return $this->cartComponentService->getComponentByClass(DataCartComponent::class)->isViewable($cart);
    }
}
