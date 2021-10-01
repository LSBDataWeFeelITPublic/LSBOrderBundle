<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use LSB\CartBundle\Module\BaseCartItemsModule;
use LSB\ContractorBundle\Entity\Contractor;
use LSB\ContractorBundle\Entity\ContractorInterface;
use LSB\LocaleBundle\Manager\TaxManager;
use LSB\OrderBundle\CartComponent\DataCartComponent;
use LSB\OrderBundle\CartModule\CartItemCartModule;
use LSB\OrderBundle\CartModule\CartModuleInterface;
use LSB\OrderBundle\CartModule\DataCartModule;
use LSB\OrderBundle\CartModule\PackageShippingCartModule;
use LSB\OrderBundle\CartModule\PackageSplitCartModule;
use LSB\OrderBundle\CartModule\PaymentCartModule;
use LSB\OrderBundle\Entity\Cart;
use LSB\OrderBundle\Entity\CartInterface;
use LSB\OrderBundle\Entity\CartItem;
use LSB\OrderBundle\Entity\CartPackageInterface;
use LSB\OrderBundle\Manager\CartManager;
use LSB\OrderBundle\Model\CartItemModule\CartItemRequestProductData;
use LSB\OrderBundle\Model\CartItemModule\CartItemUpdateResult;
use LSB\OrderBundle\Model\CartPaymentMethodCalculatorResult;
use LSB\OrderBundle\Model\CartShippingMethodCalculatorResult;
use LSB\OrderBundle\Model\CartSummary;
use LSB\PricelistBundle\Manager\PricelistManager;
use LSB\ProductBundle\Entity\Product;
use LSB\ProductBundle\Manager\ProductManager;
use LSB\ProductBundle\Manager\StorageManager;
use LSB\ProductBundle\Service\StorageService;
use LSB\UserBundle\Entity\User;
use LSB\UserBundle\Entity\UserInterface;
use LSB\UtilityBundle\Helper\ValueHelper;
use LSB\UtilityBundle\Value\Value;
use Money\Money;
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
     * @param StorageService $storageService
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
        protected CartComponentService     $cartComponentService,
        protected StorageService           $storageService
    ) {
    }

    /**
     * @param CartInterface $cart
     * @throws \Exception
     */
    public function updateCart(CartInterface $cart): void
    {
        $this->moduleManager->getModuleByName(DataCartModule::NAME)->getDataCartComponent()->getCartManager()->update($cart);
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
        ?bool                $forceReload = false,
        ?UserInterface       $user = null,
        ?ContractorInterface $contractor = null,
        bool                 $requireCreate = true,
        ?string              $cartSessionId = null,
        ?string              $cartTransactionId = null
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

    /**
     * @param string $uuid
     * @return Cart|null
     */
    public function getCartByUuid(string $uuid): ?Cart
    {
        return $this->cartManager->getByUuid($uuid);
    }

    /**
     * @param string $uuid
     * @return CartInterface|null
     */
    public function getCartByTransactionId(string $uuid): ?CartInterface
    {
        return $this->cartComponentService->getComponentByClass(DataCartComponent::class)->getCartManager()->getRepository()->getByTransactionId($uuid);
    }

    /**
     * @param Cart $sessionCart
     * @param Cart $storedCart
     * @return Cart
     * @throws \Exception
     */
    public function mergeCarts(Cart $sessionCart, Cart $storedCart): CartInterface
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
     * @throws \Exception
     */
    protected function convertSessionCartToUserCart(Cart $cart, UserInterface $user = null)
    {
        $cartDataModule = $this->moduleManager->getModuleByName(DataCartModule::NAME);
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
     * Przekazujemy tablicę
     * [
     *  $productId => $quantity
     * ]
     *
     * Dodaje lub uaktualnia pozycję w koszyku na podstawie productId i ilości
     * $updateAllCartItems - argument pozwala wymusić przeliczenie wszystkich pozycji w koszyku, jeżeli występuje zależność pomiędzy zmianą jeden pozycji, a pozostałymi
     * Przeliczanie wszystkich pozycji należy stosować wyłącznie przy obsłudzę zmian pozycji z poziomu widoku kroku 1 koszyka
     * Dodając lub zwiększając ilość dla danej pozycji z poziomu widoku produktu nie ma potrzeby przeliczania pozostałych pozycji
     *
     * @param Cart $cart
     * @param array $updateData
     * @param bool $increaseQuantity
     * @param bool $updateAllCartItems
     * @param bool $fromOrderTemplate
     * @param bool $merge
     * @return CartItemUpdateResult
     * @throws \Exception
     */
    public function updateCartItems(
        Cart  $cart,
        array $updateData,
        bool  $increaseQuantity = false,
        bool  $updateAllCartItems = false,
        bool  $fromOrderTemplate = false,
        bool  $merge = false
    ): CartItemUpdateResult {
        $cartItemModule = $this->moduleManager->getModuleByName(CartItemCartModule::NAME);
        $content = $cartItemModule->updateCartItems($cart, $updateData, $increaseQuantity, $updateAllCartItems, $fromOrderTemplate, $merge);
        $cartItemModule->validateDependencies($cart);

        return $content;
    }

    /**
     * @param CartItem $cartItem
     * @param CartItemRequestProductData|null $productDataRow
     * @return CartItem
     * @throws \Exception
     */
    public function checkQuantityAndPriceForCartItem(
        CartItem                    $cartItem,
        ?CartItemRequestProductData $productDataRow = null
    ): CartItem {
        /**
         * @var CartItemCartModule $cartItemsModule
         */
        $cartItemsModule = $this->moduleManager->getModuleByName(CartItemCartModule::NAME);

        if (!$productDataRow) {
            $productDataRow = new CartItemRequestProductData(
                null,
                null,
                null,
                null,
                null
            );
        }

        return $cartItemsModule->checkQuantityAndPriceForCartItem($cartItem, $productDataRow);
    }

    /**
     * @throws \Exception
     */
    public function rebuildCart(CartInterface $cart, bool $flush = true, bool $getCartSummary = true)
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


        $cartItems = $cart->getCartItems();
        $this->storageService->clearReservedQuantityArray();

        /**
         * @var \LSB\CartBundle\Entity\CartItem $cartItem
         */
        foreach ($cartItems as $cartItem) {
            $this->checkQuantityAndPriceForCartItem($cartItem);

            if ($cartItem->getId() === null) {
                $cartItemRemoved = true;
            }
        }

        //sprawdzenie domyślnego typu podziału paczek
        $this->checkForDefaultCartOverSaleType($cart);

        $packagesUpdated = $this->updatePackages($cart);

        if ($packagesUpdated) {
            $cart->setValidatedStep(null);
        }

        //Jeżeli z koszyka usunięte zostały jakieś produkty, wówczas
        if ($removedUnavailableProducts || $cartItemRemoved || $packagesUpdated) {
            $cart->clearCartSummary();
        }

        if ($flush) {
            $this->em->flush();
        }


        return [$cart, $cartItemRemoved || $packagesUpdated];
    }

    /**
     * @param Cart $cart
     * @return CartItemUpdateResult
     * @throws \Exception
     */
    public function rebuildCartItems(
        Cart $cart
    ): CartItemUpdateResult {
        $cartItemModule = $this->moduleManager->getModuleByName(CartItemCartModule::NAME);
        return $cartItemModule->rebuildAndProcessCartItems($cart);
    }

    /**
     * Usuwa produkty niedostępne dla płatnika/użytkownika
     *
     * @param Cart $cart
     * @return bool
     * @throws \Exception
     * @deprecated
     */
    protected function removeUnavailableProducts(Cart $cart): bool
    {
        $cartItemsModule = $this->moduleManager->getModuleByName(CartItemCartModule::NAME);
        return $cartItemsModule->removeUnavailableProducts($cart);
    }

    /**
     * @param $cart
     * @return bool
     * @throws \Exception
     */
    public function checkForDefaultCartOverSaleType($cart): bool
    {
        /** @var PackageSplitCartModule $packageSplitModule */
        $packageSplitModule = $this->moduleManager->getModuleByName(PackageSplitCartModule::NAME);
        return $packageSplitModule->checkForDefaultCartOverSaleType($cart);
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
        bool $injectCartItemsSummary = true,
        bool $rebuildItems = false,
        bool $skipItemsCountCheck = true
    ): CartSummary {
        if (!$cart) {
            $cart = $this->getCart();
        }

        $itemsCount = $cart->getCartItems()->count();

        if ($injectCartItemsSummary && ($itemsCount > 0 || $skipItemsCountCheck)) {
            $cartItemsModule = $this->moduleManager->getModuleByName(CartItemCartModule::NAME);
            $cartItemsModule->injectPricesToCartItems($cart, true);
        }

        $cartDataModule = $this->moduleManager->getModuleByName(DataCartModule::NAME);
        return $cartDataModule->getCartSummary($cart, $rebuildItems && ($itemsCount > 0 || $skipItemsCountCheck));
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
     * @param Cart $cart
     * @param bool $splitSupplier
     * @return bool|null
     * @throws \Exception
     */
    public function updatePackages(CartInterface $cart, bool $splitSupplier = false): ?bool
    {
        /**
         * @var PackageSplitCartModule $packageSplitModule
         */
        $packageSplitModule = $this->moduleManager->getModuleByName(PackageSplitCartModule::NAME);
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
         * @var PackageSplitCartModule $packageSplitModule
         */
        $packageSplitModule = $this->moduleManager->getModuleByName(PackageSplitCartModule::NAME);
        $packageSplitModule->splitPackagesForSupplier($cart, $flush);
    }

    /**
     * @param CartPackageInterface $package
     * @param bool $addVat
     * @param Money|null $calculatedTotalProducts
     * @param array|null $shippingCostRes
     * @return mixed
     * @throws \Exception
     */
    public function calculatePackageShippingCost(
        CartPackageInterface $package,
        bool                 $addVat = true,
        ?Money               $calculatedTotalProducts = null,
        array                &$shippingCostRes = null
    ): CartShippingMethodCalculatorResult {
        /**
         * @var PackageShippingCartModule $packageShippingModule
         */
        $packageShippingModule = $this->moduleManager->getModuleByName(PackageShippingCartModule::NAME);

        return $packageShippingModule->calculatePackageShippingCost(
            $package,
            $addVat,
            $calculatedTotalProducts,
            $shippingCostRes
        );
    }

    /**
     * @param CartInterface $cart
     * @param bool $addVat
     * @param Money|null $calculatedTotalProducts
     * @param array|null $paymentCostRes
     * @return CartPaymentMethodCalculatorResult
     * @throws \Exception
     */
    public function calculateCartPaymentCost(
        CartInterface $cart,
        bool          $addVat = true,
        ?Money        $calculatedTotalProducts = null,
        array         &$paymentCostRes = null
    ): CartPaymentMethodCalculatorResult {
        /**
         * @var PaymentCartModule $paymentModule
         */
        $paymentModule = $this->moduleManager->getModuleByName(PaymentCartModule::NAME);

        return $paymentModule->calculatePaymentCost(
            $cart,
            $cart->getPaymentMethod(),
            $addVat,
            $calculatedTotalProducts,
            $paymentCostRes
        );
    }

    /**
     * @param CartInterface $cart
     * @param bool $flush
     * @throws \Exception
     */
    public function closeCart(CartInterface $cart, bool $flush = true): void
    {
        /**
         * @var DataCartModule $cartDataModule
         */
        $cartDataModule = $this->moduleManager->getModuleByName(DataCartModule::NAME);
        $cartDataModule->closeCart($cart, $flush);
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
    public function isViewable(?CartInterface $cart = null)
    {
        return $this->cartComponentService->getComponentByClass(DataCartComponent::class)->isViewable($cart);
    }
}
