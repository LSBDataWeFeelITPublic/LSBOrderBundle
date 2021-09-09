<?php
/**
 * Created by PhpStorm.
 * User: krzychu
 * Date: 15.02.18
 * Time: 18:03
 */

namespace LSB\OrderBundle\CartModule;

use Doctrine\ORM\UnitOfWork;
use LSB\LocaleBundle\Manager\TaxManager;
use LSB\OrderBundle\CartComponent\CartItemCartComponent;
use LSB\OrderBundle\CartComponent\DataCartComponent;
use LSB\OrderBundle\CartModule\BaseCartModule;
use LSB\OrderBundle\Entity\Cart;
use LSB\OrderBundle\Entity\CartInterface;
use LSB\OrderBundle\Entity\CartItem;
use LSB\OrderBundle\Entity\CartItemInterface;
use LSB\OrderBundle\Model\CartItemModule\CartItemProcessedData;
use LSB\OrderBundle\Model\CartItemModule\CartItemUpdateResult;
use LSB\OrderBundle\Model\CartItemModule\Notification;
use LSB\OrderBundle\Model\CartItemRequestProductData;
use LSB\OrderBundle\Model\CartItemRequestProductDataCollection;
use LSB\PricelistBundle\Model\Price;
use LSB\ProductBundle\Entity\Product;
use LSB\ProductBundle\Entity\ProductInterface;
use LSB\ProductBundle\Entity\ProductSetProduct;
use LSB\ProductBundle\Entity\Storage;
use LSB\UtilityBundle\Value\Value;
use Money\Currency;
use Money\Money;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class BaseCartItemsModule
 * @package LSB\CartBundle\Module
 */
class CartItemCartModule extends BaseCartModule
{
    const NAME = 'cartItem';

    const ALERT_MESSAGE_SUCCESS = 'success';
    const ALERT_MESSAGE_WARNING = 'warning';
    const ALERT_MESSAGE_DANGER = 'danger';

    const CART_ITEM_KEY_MESSAGE = 'message';
    const CART_ITEM_KEY_ICON = 'icon';
    const CART_ITEM_KEY_POPUP_MESSAGE = 'popupMessage';

    const REQUEST_KEY_INCREASE_QUANTITY = 'increaseQuantity';
    const REQUEST_KEY_PRODUCTS_DATA = 'productsData';
    const REQUEST_KEY_FETCH_ALL_CART_ITEMS = 'fetchAllCartItems';
    const REQUEST_KEY_CLEAR_CART = 'clearCart';

    const ITEMS_CNT_CREATED = 'created';
    const ITEMS_CNT_UPDATED = 'updated';
    const ITEMS_CNT_REMOVED = 'removed';
    const ITEMS_CNT_SKIPPED = 'skipped';

    /** @var array */
    protected $cartItemTypeUpdated = [];

    public function __construct(
        DataCartComponent               $dataCartComponent,
        protected CartItemCartComponent $cartItemCartComponent
    ) {
        parent::__construct($dataCartComponent);
    }

    /**
     * @inheritDoc
     */
    public function validate(CartInterface $cart): array
    {
        $errors = [];

        if (count($this->getDefaultCartItems($cart, true, false)) === 0) {
            $errors[] = $this->dataCartComponent->getTranslator()->trans('Cart.Module.CartItems.Validation.NoItems', [], 'Cart');
        }

        return $errors;
    }

    /**
     * @inheritDoc
     */
    public function validateDependencies(CartInterface $cart): void
    {
        parent::validateDependencies($cart);
        //jakiś sposób przekazywania walidacji do poziomu serwisu
        //$this->moduleManager->validateModule(BasePackageShippingModule::NAME, $cart);
    }

    /**
     * Zwraca listę standardowych pozycji w koszyku (produktów)
     *
     * @param Cart $cart
     * @param bool $onlySelected
     * @param bool $injectMedia
     * @return array|null
     */
    public function getDefaultCartItems(Cart $cart, bool $onlySelected = false, bool $injectMedia = true): ?array
    {
        return $cart->getCartItemsByProductType(ProductInterface::TYPE_DEFAULT, $onlySelected);
    }

    /**
     * @inheritdoc
     */
    public function getDataForRender(CartInterface $cart, ?Request $request = null): array
    {
        $parentData = parent::getDataForRender($cart, $request);
        //TODO move to calculator
        //$this->dataCartComponent->getCartSummary($cart, true);
        $defaultCartItems = $this->getDefaultCartItems($cart);

        $data = [
            'defaultCartItems' => $defaultCartItems,
        ];

        return array_merge($parentData, $data);
    }

    /**
     * @inheritDoc
     */
    public function process(?CartInterface $cart, Request $request)
    {
        if (!$cart) {
            $cart = $this->dataCartComponent->getCart();
        }

        //Sprawdzamy uprawnienia do edycji zawartości koszyka
//        if (!$this->dataCartComponent->getAuthorizationChecker()->isGranted(CartVoterInterface::ACTION_EDIT_CART_ITEMS, $cart)) {
//            throw new AccessDeniedException();
//        }

        $increaseQuantity = (bool)$request->get('increaseQuantity');
        $productsData = is_array($request->get('productsData')) ? $request->get('productsData') : [];
        $fetchAllCartItems = (bool)$request->get('fetchAllCartItems');

        $cartItemUpdateResult = $this->updateCartItems(
            $cart,
            $productsData,
            $increaseQuantity,
            $fetchAllCartItems
        );

        $this->validateDependencies($cart);

        return $this->getResponse($cartItemUpdateResult);
    }

    /**
     * Przygotowanie odpowiedzi na request zmiany pozycji
     *
     * @inheritDoc
     */
    public function getResponse(CartItemUpdateResult $cartItemUpdateResult)
    {
        return $cartItemUpdateResult;
    }

    /**
     * Fetch, calculate and inject prices to cart item
     *
     * @param CartItemInterface $cartItem
     * @param bool $setActivePrice
     * @throws \Exception
     */
    public function injectPriceToCartItem(CartItemInterface $cartItem, bool $setActivePrice = true): void
    {
        $activePrice = $this->cartItemCartComponent->getPriceForCartItem($cartItem);
        $isProductSet = $cartItem->getProduct()->isProductSet() ?? false;
        $productSetProductActivePrices = [];

        if (!$isProductSet) {
            //Wartość pozycji koszyka liczona jest na tym etapie. Bazując na jednostkowych cenach
            if ($this->dataCartComponent->getPs()->get('cart.calculation.gross')) {

                //Money
                $valueNet = $this->dataCartComponent->calculateMoneyNetValueFromGrossPrice(
                    $activePrice->getGrossPrice(true),
                    $cartItem->getQuantity(true),
                    $activePrice->getVat(true)
                );

                //Money
                $valueGross = $this->dataCartComponent->calculateMoneyGrossValue(
                    $activePrice->getGrossPrice(true),
                    $cartItem->getQuantity(true)
                );

                //Money
                $baseValueNetto = $this->dataCartComponent->calculateMoneyNetValueFromGrossPrice(
                    $activePrice->getBaseGrossPrice(true),
                    $cartItem->getQuantity(true),
                    $activePrice->getVat(true)
                );

                //Money
                $baseValueGross = $this->dataCartComponent->calculateMoneyGrossValue(
                    $activePrice->getBaseGrossPrice(true),
                    $cartItem->getQuantity(true)
                );
            } else {
                //Money
                $valueNet = $this->dataCartComponent->calculateMoneyNetValue(
                    $activePrice->getNetPrice(true),
                    $cartItem->getQuantity(true)
                );

                //Money
                $valueGross = $this->dataCartComponent->calculateMoneyGrossValueFromNetPrice(
                    $activePrice->getNetPrice(true),
                    $cartItem->getQuantity(true),
                    $activePrice->getVat(true)
                );

                //Money
                $baseValueNetto = $this->dataCartComponent->calculateMoneyNetValue(
                    $activePrice->getBaseNetPrice(true),
                    $cartItem->getQuantity(true)
                );

                //Money
                $baseValueGross = $this->dataCartComponent->calculateMoneyGrossValueFromNetPrice(
                    $activePrice->getBaseNetPrice(true),
                    $cartItem->getQuantity(true),
                    $activePrice->getVat(true)
                );
            }
        } else {
            //Wyliczamy wartość pozycji na podstawie składowych
            $cartItemTotalRes = [];
            $cartItemBaseTotalRes = [];

            /**
             * @var ProductSetProduct $productSetProduct
             */
            foreach ($cartItem->getProduct()->getProductSetProducts() as $productSetProduct) {
                //Wyciąganie active price per konkretny produkt
                $product = $productSetProduct->getProduct();
                //Currently saved as INT
                $productQuantity = $productSetProduct->getQuantity();
                $productSet = $cartItem->getProduct();

                $calculatedQuantity = $cartItem->getQuantity(true)->multiply($productQuantity);
                $productActivePrice = $this->cartItemCartComponent->getPriceForProduct($cartItem->getCart(), $product, $productSet, $calculatedQuantity);

                //Sumaryczna wycena składnika
                $productSetProductActivePrices[$product->getId()] = $productActivePrice;

                if ($this->dataCartComponent->getPs()->get('cart.calculation.gross')) {
                    $productSetProductValueNetto = $this->dataCartComponent->calculateMoneyNetValueFromGrossPrice(
                        $productActivePrice->getGrossPrice(true),
                        $calculatedQuantity,
                        $productActivePrice->getVat(true)
                    );

                    $productSetProductValueGross = $this->dataCartComponent->calculateMoneyGrossValue(
                        $activePrice->getGrossPrice(),
                        $calculatedQuantity
                    );

                    TaxManager::addValueToGrossRes(
                        $productActivePrice->getVat(true)->getRealFloatAmount(),
                        (int)$productSetProductValueGross->getAmount(),
                        $cartItemTotalRes
                    );

                    //Base
                    $productSetProductBaseValueNetto = $this->dataCartComponent->calculateMoneyNetValueFromGrossPrice(
                        $productActivePrice->getBaseGrossPrice(true),
                        $calculatedQuantity,
                        $productActivePrice->getVat(true)
                    );

                    $productSetProductBaseValueGross = $this->dataCartComponent->calculateMoneyGrossValue(
                        $activePrice->getBaseGrossPrice(true),
                        $calculatedQuantity
                    );

                    TaxManager::addValueToGrossRes($productActivePrice->getVat()->getRealFloatAmount(), $productSetProductBaseValueGross->getAmount(), $cartItemBaseTotalRes);
                } else {
                    $productSetProductValueNetto = $this->dataCartComponent->calculateMoneyNetValue(
                        $productActivePrice->getNetPrice(true),
                        $calculatedQuantity
                    );

                    $productSetProductValueGross = $this->dataCartComponent->calculateMoneyGrossValueFromNetPrice(
                        $productActivePrice->getNetPrice(true),
                        $calculatedQuantity,
                        $productActivePrice->getVat(true)
                    );

                    TaxManager::addValueToNettoRes($productActivePrice->getVat()->getRealFloatAmount(), $productSetProductValueNetto->getAmount(), $cartItemTotalRes);

                    //Base
                    $productSetProductBaseValueNetto = $this->dataCartComponent->calculateMoneyNetValue(
                        $productActivePrice->getBaseNetPrice(true),
                        $calculatedQuantity
                    );

                    $productSetProductBaseValueGross = $this->dataCartComponent->calculateMoneyGrossValueFromNetPrice(
                        $productActivePrice->getBaseNetPrice(true),
                        $calculatedQuantity,
                        $productActivePrice->getVat(true)
                    );

                    TaxManager::addValueToNettoRes($productActivePrice->getVat()->getRealFloatAmount(), $productSetProductBaseValueNetto->getAmount(), $cartItemBaseTotalRes);
                }
            }

            //Wyliczamy sumaryczną wartość pozycji na podstawie składowych
            //zaokrąglamy na samym końcu
            if ($this->dataCartComponent->getPs()->get('cart.calculation.gross')) {
                //TODO przerobić na money
                [$valueNet, $valueGross] = TaxManager::calculateTotalNettoAndGrossFromGrossRes($cartItemTotalRes, $this->dataCartComponent->addTax($cartItem->getCart()));
                [$baseValueNetto, $baseValueGross] = TaxManager::calculateTotalNettoAndGrossFromGrossRes($cartItemBaseTotalRes, $this->dataCartComponent->addTax($cartItem->getCart()));
            } else {
                //TODO przerobić na money
                [$valueNet, $valueGross] = TaxManager::calculateTotalNettoAndGrossFromNettoRes($cartItemTotalRes, $this->dataCartComponent->addTax($cartItem->getCart()));
                [$baseValueNetto, $baseValueGross] = TaxManager::calculateTotalNettoAndGrossFromNettoRes($cartItemBaseTotalRes, $this->dataCartComponent->addTax($cartItem->getCart()));
            }
        }

        $taxValue = $valueGross->subtract($valueNet);

        $cartItem
            ->getCartItemSummary()
            ->setPriceNet($activePrice->getNetPrice(true))
            ->setPriceGross($activePrice->getGrossPrice(true))
            ->setBasePriceNet($activePrice->getBaseNetPrice(true))
            ->setBasePriceGross($activePrice->getBaseGrossPrice(true))
            ->setValueNet($valueNet)
            ->setValueGross($valueGross)
            ->setBaseValueNet($baseValueNetto)
            ->setBaseValueGross($baseValueGross)
            ->setTaxValue($taxValue)
            //->setTax($this->dataCartComponent->addTax($cart) ? $activePrice->getVat() : null)
            //->setRes($activePrice->getProcedureResult())
            ->setQuantity($cartItem->getQuantity())
            ->setCalculatedAt(new \DateTime('now'))
            ->setActivePrice($setActivePrice ? $activePrice : null)
            ->setCurrencyIsoCode((string)$activePrice->getCurrencyIsoCode())
            ->setIsProductSet($isProductSet)
            ->setProductSetProductActivePrices($productSetProductActivePrices);

        //Dodatkowo w celach historycznych i kontrolnych przepisujemy wyliczone wartości do encji
        $cartItemSummary = $cartItem->getCartItemSummary();

        //Niestety występuje rozbieżność w konwencji nazw kolumn
        /**
         * @var CartItem $cartItem
         */
        $cartItem
            ->setPriceNet($cartItemSummary->getPriceNet())
            ->setValueNet($cartItemSummary->getValueNet())
            ->setPriceGross($cartItemSummary->getPriceGross())
            ->setValueGross($cartItemSummary->getValueGross());
    }

    /**
     * The method injects prices to the cart items
     *
     * @param Cart $cart
     * @param bool $setActivePrice
     * @throws \Exception
     */
    public function injectPricesToCartItems(CartInterface $cart, bool $setActivePrice = true): void
    {
        $cartItems = $cart->getCartItems();

        /**
         * @var CartItem $cartItem
         */
        foreach ($cartItems as $cartItem) {
            if ($cartItem->getCartItemSummary() && $cartItem->getCartItemSummary()->getCalculatedAt()) {
                continue;
            }

            $this->injectPriceToCartItem($cartItem, $setActivePrice);
        }
    }

    /**
     * @param CartItem $selectedCartItem
     * @param Price $activePrice
     * @param array $totalRes
     * @param array $spreadRes
     * @param float|null $catalogueValueNetto
     * @param float|null $catalogueValueGross
     */
    protected function calculateActiveValues(
        CartItem $selectedCartItem,
        Price    $activePrice,
        array    &$totalRes,
        array    &$spreadRes,
        ?float   $catalogueValueNetto,
        ?float   $catalogueValueGross
    ): void {
        $vat = $activePrice->getVat();

        if ($this->dataCartComponent->getPs()->get('cart.calculation.gross')) {
            $valueNetto = $this->dataCartComponent->calculateNetValueFromGrossPrice($activePrice->getGrossPrice(), $selectedCartItem->getQuantity(), $activePrice->getVat());
            $valueGross = $this->dataCartComponent->calculateGrossValue($activePrice->getGrossPrice(), $selectedCartItem->getQuantity());

            TaxManager::addValueToGrossRes($vat, $valueGross, $totalRes);
            if ($catalogueValueGross !== null) {
                TaxManager::addValueToGrossRes($vat, ($catalogueValueGross > $valueGross) ? $catalogueValueGross - $valueGross : 0, $spreadRes);
            }
        } else {
            $valueNetto = $this->dataCartComponent->calculateNetValue($activePrice->getNetPrice(), $selectedCartItem->getQuantity());
            $valueGross = $this->dataCartComponent->calculateGrossValueFromNetPrice($activePrice->getNetPrice(), $selectedCartItem->getQuantity(), $activePrice->getVat());

            TaxManager::addValueToNettoRes($vat, $valueNetto, $totalRes);
            if ($catalogueValueNetto !== null) {
                TaxManager::addValueToNettoRes($vat, ($catalogueValueNetto > $valueNetto) ? $catalogueValueNetto - $valueNetto : 0, $spreadRes);
            }
        }
    }

    /**
     * Zwraca informacje o jednej pozycji koszyka
     *
     * @param CartItem $cartItem
     * @param bool $fetchPrice
     * @return CartItem
     * @throws \Exception
     * @deprecated
     */
    public function processCartItemResult(CartItem $cartItem, bool $fetchPrice = true): CartItem
    {
        return $cartItem;
    }

    /**
     * @param CartItem $existingCartItem
     * @param CartItemRequestProductData $cartItemRequestProductData
     * @param bool $increaseQuantity
     * @param CartItemUpdateResult $result
     * @param bool $update
     * @param bool $merge
     * @return bool
     */
    protected function processQuantityForExistingCartItem(
        CartItem                   $existingCartItem,
        CartItemRequestProductData $cartItemRequestProductData,
        bool                       $increaseQuantity,
        CartItemUpdateResult       $result,
        bool                       $update,
        bool                       $merge = false
    ): bool {

        $quantity = $cartItemRequestProductData->getQuantity()?->getAmount() ?: 0;
        $isSelected = $cartItemRequestProductData->isSelected();

        if ($quantity !== null && $quantity <= 0) {

            //TODO refactor voters
//            if (!$merge && !$this->dataCartComponent->getAuthorizationChecker()->isGranted(CartVoterInterface::ACTION_REMOVE_CART_ITEMS, $existingCartItem->getCart())) {
//                throw new \Symfony\Component\Security\Core\Exception\AccessDeniedException();
//            }

            //usuwamy pozycję
            $this->dataCartComponent->getCartItemManager()->remove($existingCartItem);
            $cartItemRequestProductData->markAsRemoved();

            $result->getProcessedItems()->getUpdateCounter()->increaseRemoved();

            $cartItemRequestProductData->createSuccessNotification(
                $this->dataCartComponent->getTranslator()->trans(
                    'Cart.Module.CartItems.AlertMessage.ProductRemovedFromCart',
                    [
                        '%productName%' => $this->dataCartComponent->getPs()->get(
                            'cart.showProductOrderCodeInMessage'
                        ) ? $existingCartItem->getOrderCode() : $existingCartItem->getProduct()->getName(),
                        '%quantity%' => ($existingCartItem->getQuantity()),
                    ],
                    'Cart'
                ));

        } elseif ($quantity !== null && $quantity > 0 && $increaseQuantity) {
//            if (!$merge && !$this->dataCartComponent->getAuthorizationChecker()->isGranted(CartVoterInterface::ACTION_EDIT_CART_ITEMS, $existingCartItem->getCart())) {
//                throw new \Symfony\Component\Security\Core\Exception\AccessDeniedException();
//            }

            $existingCartItem->increaseQuantity($cartItemRequestProductData->getQuantity(true));
            $update = true;
        } elseif ($quantity !== null && $quantity > 0) {
//            if (!$merge && !$this->dataCartComponent->getAuthorizationChecker()->isGranted(CartVoter::ACTION_EDIT_CART_ITEMS, $existingCartItem->getCart())) {
//                throw new \Symfony\Component\Security\Core\Exception\AccessDeniedException();
//            }

            $existingCartItem->setQuantity($quantity);
            $update = true;
        }

        if ($isSelected !== null) {
            //mamy true or false
            $existingCartItem->setIsSelected($isSelected);
            $update = true;
        }

        //TODO add isSelectedForOption support

        return $update;
    }

    /**
     * @return bool
     */
    protected function isZeroPriceAllowed(): bool
    {
        return false;
    }

    /**
     * Przekazujemy tablicę
     * [
     *  $productId => $quantity
     * ]
     *
     * Dodaje lub uaktualnia pozycję w koszyku na podstawie productDetail i ilości
     * $updateAllCartItems - argument pozwala wymusić przeliczenie wszystkich pozycji w koszyku, jeżeli występuje zależność pomiędzy zmianą jeden pozycji, a pozostałymi
     * Przeliczanie wszystkich pozycji należy stosować wyłącznie przy obsłudzę zmian pozycji z poziomu widoku kroku 1 koszyka
     * Dodając lub zwiększając ilość dla danej pozycji z poziomu widoku produktu nie ma potrzeby przeliczania pozostałych pozycji
     *
     * @param Cart|null $cart
     * @param array $updateData
     * @param bool $increaseQuantity
     * @param bool $updateAllCartItems
     * @param bool $fromOrderTemplate
     * @param bool $merge
     * @return CartItemUpdateResult
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function updateCartItems(
        CartInterface $cart = null,
        array         $updateData,
        bool          $increaseQuantity = false,
        bool          $updateAllCartItems = false,
        bool          $fromOrderTemplate = false,
        bool          $merge = false
    ): CartItemUpdateResult {
        //Weryfikacja uprawnień powinna odbyć się z poziomu modułu
        $updateResult = new CartItemUpdateResult();
        //Potrzebujemy kluczy int, reindexujemy tablicę
        $updateData = array_values($updateData);
        $updateData = $this->cartItemCartComponent->prepareCartItemsUpdateDataCollection($updateData, $updateResult);
        [$productIds, $orderCodes] = $this->cartItemCartComponent->prepareCartItemsUpdateDataArray($updateData, $updateResult);

        //Jeżeli request nie zawiera kompletnych danych wyrzucamy exception
        if (count($productIds) == 0) {
            throw new \Exception('Missing update data for cart items');
        }

        $update = false;

        //Fetching cart item position from database
        if ($cart->getId()) {
            $existingCartItems = $this->dataCartComponent->getCartItemManager()->getRepository()->getCartItemsByCartProductAndOrderCodes(
                $cart->getId(),
                $updateData
            );
        } else {
            //Cart is not persisted yet, we assume, that there is no cart items
            $existingCartItems = [];
        }

        if (count($existingCartItems)) {
            /**
             * @var CartItem $existingCartItem
             */
            foreach ($existingCartItems as $existingCartItem) {
                $this->cartItemTypeUpdated[$existingCartItem->getProduct()->getType()] = true;
                $orderCode = $existingCartItem->getOrderCode() ? $existingCartItem->getOrderCode() : CartItem::DEFAULT_ORDER_CODE_VALUE;

                if ($updateData->get($existingCartItem->getProduct()->getUuid(), $orderCode)) {
                    $cartItemProductDataRequest = $updateData->get($existingCartItem->getProduct()->getUuid(), $orderCode);
                } else {
                    continue;
                }

                $isUpdatedRequired = $this->processQuantityForExistingCartItem(
                    $existingCartItem,
                    $cartItemProductDataRequest,
                    $increaseQuantity,
                    $updateResult,
                    $update,
                    $merge
                );

                if ($isUpdatedRequired) {
                    $this->cartItemCartComponent->checkQuantityAndPriceForCartItem(
                        $existingCartItem,
                        $cartItemProductDataRequest
                    );

                    $updateResult->getProcessedItems()->getUpdateCounter()->increaseUpdated();
                    $cartItemProductDataRequest
                        ->markAsUpdated()
                        ->setCartItem($existingCartItem);

                    //Informacja o aktualizacji ilości sztuk uzależniona jest od parametru
                    if ($cartItemProductDataRequest->getQuantity() !== null
                        && $this->dataCartComponent->getPs()->get('cart.notification.quantity_change.show')
                    ) {
                        $cartItemProductDataRequest->createSuccessNotification(
                            $this->dataCartComponent->getTranslator()->trans(
                                'Cart.Module.CartItems.AlertMessage.ProductQuantityChange',
                                [
                                    '%productName%' => $this->dataCartComponent->getPs()->get(
                                        'cart.showProductOrderCodeInMessage'
                                    ) ? $existingCartItem->getOrderCode() : $existingCartItem->getProduct()->getName(),
                                    '%quantity%' => ($existingCartItem->getQuantity()),
                                ],
                                'Cart'
                            ));
                    }
                }

                $cartItemProductDataRequest
                    ->markAsSkipped()
                    ->setCartItem($existingCartItem);

                $this->injectPriceToCartItem($existingCartItem);
            }
        }

        // Niestniejące pozycje traktujemy jako nowe,
        // weryfikujemy grupowo czy wskazane detailsy istnieją faktycznie w bazie, otrzymyjemy tylko zweryfikowane ID produktu
        // jeżeli details jest aktywny i spelnia warunki filtrowania produktu!
        //Jeżeli mamy do czynienia z zestawem produktów uzupełniamy zawartość listy

        $fetchedProductUuids = $this->dataCartComponent->getProductManager()->getRepository()->checkEnabledProductUuids(
            $productIds
        );

        //weryfikujemy, które product details są dostępne dla użytkownika EDI - wsparcie pod kątem pierwszego wydania
        $availableProductsForCustomer = [];

        if (!$this->dataCartComponent->getPs()->get('cart.products.all_available_for_user') && $cart->getBillingContractor()) {
            //Pulę produktów do weryfikacji uzupełniamy o ID produktów wchodzących w skład zestawów
            //Na podstawie ID produktów przekazanych do weryfikacji, pobieramy ID zestawów

            $productSetIds = $this->dataCartComponent->getProductManager()->getRepository()->getProductSetsIdsByProductUuids(array_values($productIds));
            $productSetProductUuids = $this->dataCartComponent->getProductSetProductManager()->getRepository()->getProductSetProductIds($productSetIds, true);

//            $availableProductsForCustomer = $this->customerAvailabilityManager->areProductsAvailableForCustomerByUuids(
//                array_merge($productIds, $productSetProductUuids),
//                $cart->getCustomer(),
//                $this->applicationManager->getApplication()
//            );

            $availableProductsForCustomer = [];
        }

        //Dane produktów, które wcześniej nie istniały w koszyku
        //Konieczne jest utworzone nowych pozycji


        if (count($updateData)) {
            /** @var array $orderCodeDetailDataRow */

            foreach ($updateData->getCollection() as $key => $orderCodeDetailDataRow) {
                /**
                 * @var CartItemRequestProductData $productDataRow
                 */
                foreach ($orderCodeDetailDataRow as $productDataRow) {

                    //Pomijamy przetworzone wcześniej pozycje
                    if ($productDataRow->isProcessed()) {
                        continue;
                    }

                    if (!$this->cartItemCartComponent->canCreateNewCartItem(
                        $cart,
                        $productDataRow,
                        $availableProductsForCustomer,
                        $fromOrderTemplate,
                        true
                    )) {
                        $updateResult->getProcessedItems()->getUpdateCounter()->increaseSkipped();
                        $productDataRow->markAsSkipped();
                        continue;
                    }

                    //Created cart item
                    $cartItem = $this->cartItemCartComponent->createNewCartItem($cart, $productDataRow);

                    $this->cartItemTypeUpdated[$cartItem->getProduct()->getType()] = true;

                    if ($cart->getDeliveryVariant() && $cart->getDeliveryVariant() === CartInterface::DELIVERY_VARIANT_ONLY_AVAILABLE) {
                        $cart->setIsDeliveryVariantSelected(false);
                    }

                    $this->cartItemCartComponent->checkQuantityAndPriceForCartItem($cartItem, $productDataRow);

                    $productDataRow->createSuccessNotification($this->dataCartComponent->getTranslator()->trans(
                        'Cart.Module.CartItems.AlertMessage.AddedNewProductToCart',
                        [
                            '%productName%' => $this->dataCartComponent->getPs()->get(
                                'cart.showProductOrderCodeInMessage'
                            ) ? $cartItem->getOrderCode() : $cartItem->getProduct()->getName(),
                            '%quantity%' => ($cartItem->getQuantity()),
                        ],
                        'Cart'
                    ));

                    $updateResult->getProcessedItems()->getUpdateCounter()->increaseCreated();
                    $productDataRow
                        ->markAsCreated()
                        ->setCartItem($cartItem);

                    $this->injectPriceToCartItem($cartItem);
                }
            }
        }


        //Aktualizacja pozycji w bazie, odświeżenie obiektu koszyka
        $this->dataCartComponent->getCartManager()->flush();
        //TODO implement in ObjectManager

        //$this->dataCartComponent->getCartManager()->refresh($cart);

        if ($updateAllCartItems) {
            //Przeliczenie wszystkich pozycji i konwersja do tablicy -
            //TODO odblokować
            //$processedItemsData = $this->rebuildAndProcessCartItems($cart);
        }

        //Flush jest niezbędny
        $this->dataCartComponent->getCartManager()->flush();
        //Dodane w w celu weryfikacji problemu problemy z rozbieżnością pomiędzy cartItems na packageItem
        // $this->dataCartComponent->getCartManager()->refresh($cart);

        //Sprawdzamy domyślny sposób podziału paczki
        //TODO
        //$this->dataCartComponent->checkForDefaultCartOverSaleType($cart);

        //uaktualniamy paczki
        //TODO
        //$this->dataCartComponent->updatePackages($cart);

        //Aktualizacja cen wszystkich dostępnych pozycjach cartItems
        return $updateResult;
    }

    /**
     * Sprawdza aktualne stany magazynowe i koryguje ilość w koszyku, zwraca informacje o zamianach w postaci tablicy notyfikacji
     *
     * @param Cart $cart
     * @return array
     * @throws \Exception
     */
    public function checkStorage(Cart $cart): array
    {
        $selectedCartItems = $cart->getSelectedCartItems();

        //przygotowujemy notyfikacje jezeli stan magazynowy jest mniejszy od żądanego
        $notifications = [];
        //TODO docelowo do przygotowania metodka wyciągająca stany produktów, grupowo do tablicy

        foreach ($selectedCartItems as $selectedCartItem) {
            //TODO after packegeComponent
            //$this->dataCartComponent->checkQuantityAndPriceForCartItem($selectedCartItem, $notifications);
        }
        $this->dataCartComponent->getCartManager()->flush();

        return $notifications;
    }

    /**
     * Recalculates cart items, verifies availability, returns CartItemUpdateResult
     * @param Cart|null $cart
     * @param bool $flush
     * @return CartItemUpdateResult
     * @throws \Exception
     */
    public function rebuildAndProcessCartItems(Cart $cart = null, bool $flush = true): CartItemUpdateResult
    {
        //$this->dataCartComponent->getStorageManager()->clearReservedQuantityArray();

        $result = new CartItemUpdateResult();
        $cartItemRequestProductDataCollection = new CartItemRequestProductDataCollection();

        if (!$cart) {
            $cart = $this->dataCartComponent->getCart();
        }

        /**
         * @var CartItem $cartItem
         */
        foreach ($cart->getCartItems() as $cartItem) {
            $productDataRow = new CartItemRequestProductData(
                (string)$cartItem->getProduct()->getUuid(),
                $cartItem->getProductSet()?->getUuid(),
                $cartItem->getOrderCode(),
                $cartItem->getQuantity(true),
                $cartItem->getProductSetQuantity(true),
                $cartItem->isSelected(),
                $cartItem->isSelectedForOption(),
                null,
                $cartItem
            );

            $cartItemRequestProductDataCollection->add($productDataRow);

            $this->cartItemCartComponent->checkQuantityAndPriceForCartItem($cartItem, $productDataRow);
            //UWAGA! Sprawdzamy czy któraś z pozycji nie zostałą usunięta, w przypadku wyłączenia softdelete należy sprawdzić id obiektu
            $entityState = $this->dataCartComponent->getCartManager()->getObjectManager()->getUnitOfWork()->getEntityState($cartItem);

            if ($entityState === UnitOfWork::STATE_MANAGED) {
                $productDataRow->markAsUpdated();
            } elseif ($entityState == UnitOfWork::STATE_NEW && $cartItem->getProduct()) {
                $productDataRow->markAsCreated();
            } else {
                $productDataRow->markAsRemoved();
            }
        }

        if ($flush) {
            $this->dataCartComponent->getCartManager()->flush();
        }

        /**
         * @var CartItemInterface $cartItem
         */
        foreach ($cart->getCartItems() as $cartItem) {
            $this->injectPriceToCartItem($cartItem);
        }

        $result->getProcessedItems()->setUpdateData($cartItemRequestProductDataCollection);
        return $result;
    }

    /**
     * Pobiera tablicę z typami pozycji, które zostało zaktualizowane podczas obecnego requesta
     * @depracted
     *
     * @return array
     */
    public function getCartItemTypeUpdated()
    {
        return $this->cartItemTypeUpdated;
    }

    /**
     * Weryfikacja obecności ordercode przy dodawaniu pozycji do koszyka
     *
     * @param string|null $orderCode
     * @return bool
     */
    protected function isOrderCodeSet(?string $orderCode): bool
    {
        if ($orderCode && trim($orderCode) != '') {
            return true;
        }

        return false;
    }

    /**
     * Przygotowanie danych przed renderowaniem modułu
     *
     * @param Cart $cart
     * @throws \Exception
     */
    public function prepare(CartInterface $cart)
    {
        $this->validateDependencies($cart);
    }
}
