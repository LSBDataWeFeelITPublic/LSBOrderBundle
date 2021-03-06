<?php
declare(strict_types=1);

namespace LSB\OrderBundle\CartModule;

use Doctrine\ORM\UnitOfWork;
use LSB\LocaleBundle\Manager\TaxManager;
use LSB\OrderBundle\CartComponent\CartItemCartComponent;
use LSB\OrderBundle\CartComponent\DataCartComponent;
use LSB\OrderBundle\CartComponent\PackageSplitCartComponent;
use LSB\OrderBundle\CartHelper\PriceHelper;
use LSB\OrderBundle\Entity\Cart;
use LSB\OrderBundle\Entity\CartInterface;
use LSB\OrderBundle\Entity\CartItem;
use LSB\OrderBundle\Entity\CartItemInterface;
use LSB\OrderBundle\Manager\CartManager;
use LSB\OrderBundle\Model\CartItemModule\CartItemUpdateResult;
use LSB\OrderBundle\Model\CartItemModule\CartItemRequestProductData;
use LSB\OrderBundle\Model\CartItemModule\CartItemRequestProductDataCollection;
use LSB\PricelistBundle\Model\Price;
use LSB\ProductBundle\Entity\Product;
use LSB\ProductBundle\Entity\ProductInterface;
use LSB\ProductBundle\Entity\ProductSetProduct;
use LSB\ProductBundle\Interfaces\ProductTypeInterface;
use LSB\UtilityBundle\Helper\ValueHelper;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;

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
        CartManager $cartManager,
        DataCartComponent                   $dataCartComponent,
        protected CartItemCartComponent     $cartItemCartComponent,
        protected PackageSplitCartComponent $packageSplitCartComponent,
        protected PriceHelper               $priceHelper,
        protected ParameterBagInterface $ps
    ) {
        parent::__construct(
            $cartManager,
            $dataCartComponent
        );
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
        //jaki?? spos??b przekazywania walidacji do poziomu serwisu
        //$this->moduleManager->validateModule(BasePackageShippingModule::NAME, $cart);
    }

    /**
     * Zwraca list?? standardowych pozycji w koszyku (produkt??w)
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

        //Sprawdzamy uprawnienia do edycji zawarto??ci koszyka
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
     * TODO move to cart calulator?
     *
     * @param CartItemInterface $cartItem
     * @param bool $setActivePrice
     * @throws \Exception
     */
    public function injectPriceToCartItem(CartItemInterface $cartItem, bool $setActivePrice = true): void
    {

        $activePrice = $this->priceHelper->getPriceForCartItem($cartItem);
        $isProductSet = $cartItem->getProduct()->isProductSet() ?? false;
        $productSetProductActivePrices = [];

        if (!$activePrice instanceof Price) {
            return;
        }

        if (!$isProductSet) {
            if ($this->dataCartComponent->getPs()->get('cart.calculation.gross')) {

                //Money
                $valueNet = $this->priceHelper->calculateMoneyNetValueFromGrossPrice(
                    $activePrice->getGrossPrice(true),
                    $cartItem->getQuantity(true),
                    $activePrice->getVat(true)
                );

                //Money
                $valueGross = $this->priceHelper->calculateMoneyGrossValue(
                    $activePrice->getGrossPrice(true),
                    $cartItem->getQuantity(true)
                );

                //Money
                $baseValueNetto = $this->priceHelper->calculateMoneyNetValueFromGrossPrice(
                    $activePrice->getBaseGrossPrice(true),
                    $cartItem->getQuantity(true),
                    $activePrice->getVat(true)
                );

                //Money
                $baseValueGross = $this->priceHelper->calculateMoneyGrossValue(
                    $activePrice->getBaseGrossPrice(true),
                    $cartItem->getQuantity(true)
                );
            } else {
                //Money
                $valueNet = $this->priceHelper->calculateMoneyNetValue(
                    $activePrice->getNetPrice(true),
                    $cartItem->getQuantity(true)
                );

                //Money
                $valueGross = $this->priceHelper->calculateMoneyGrossValueFromNetPrice(
                    $activePrice->getNetPrice(true),
                    $cartItem->getQuantity(true),
                    $activePrice->getVat(true)
                );

                //Money
                $baseValueNetto = $this->priceHelper->calculateMoneyNetValue(
                    $activePrice->getBaseNetPrice(true),
                    $cartItem->getQuantity(true)
                );

                //Money
                $baseValueGross = $this->priceHelper->calculateMoneyGrossValueFromNetPrice(
                    $activePrice->getBaseNetPrice(true),
                    $cartItem->getQuantity(true),
                    $activePrice->getVat(true)
                );
            }
        } else {
            //Wyliczamy warto???? pozycji na podstawie sk??adowych
            $cartItemTotalRes = [];
            $cartItemBaseTotalRes = [];

            /**
             * @var ProductSetProduct $productSetProduct
             */
            foreach ($cartItem->getProduct()->getProductSetProducts() as $productSetProduct) {
                //Wyci??ganie active price per konkretny produkt
                $product = $productSetProduct->getProduct();
                //Currently saved as INT
                $productQuantity = $productSetProduct->getQuantity();
                $productSet = $cartItem->getProduct();

                $calculatedQuantity = $cartItem->getQuantity(true)->multiply($productQuantity);
                $productActivePrice = $this->priceHelper->getPriceForProduct($cartItem->getCart(), $product, $productSet, $calculatedQuantity);

                //Sumaryczna wycena sk??adnika
                $productSetProductActivePrices[$product->getId()] = $productActivePrice;

                if ($this->dataCartComponent->getPs()->get('cart.calculation.gross')) {
                    $productSetProductValueNetto = $this->priceHelper->calculateMoneyNetValueFromGrossPrice(
                        $productActivePrice->getGrossPrice(true),
                        $calculatedQuantity,
                        $productActivePrice->getVat(true)
                    );

                    $productSetProductValueGross = $this->priceHelper->calculateMoneyGrossValue(
                        $activePrice->getGrossPrice(),
                        $calculatedQuantity
                    );

                    TaxManager::addMoneyValueToGrossRes(
                        $productActivePrice->getVat(true),
                        $productSetProductValueGross,
                        $cartItemTotalRes
                    );

                    //Base
                    $productSetProductBaseValueNetto = $this->priceHelper->calculateMoneyNetValueFromGrossPrice(
                        $productActivePrice->getBaseGrossPrice(true),
                        $calculatedQuantity,
                        $productActivePrice->getVat(true)
                    );

                    $productSetProductBaseValueGross = $this->priceHelper->calculateMoneyGrossValue(
                        $activePrice->getBaseGrossPrice(true),
                        $calculatedQuantity
                    );

                    TaxManager::addMoneyValueToGrossRes($productActivePrice->getVat(true), $productSetProductBaseValueGross, $cartItemBaseTotalRes);
                } else {
                    $productSetProductValueNetto = $this->priceHelper->calculateMoneyNetValue(
                        $productActivePrice->getNetPrice(true),
                        $calculatedQuantity
                    );

                    $productSetProductValueGross = $this->priceHelper->calculateMoneyGrossValueFromNetPrice(
                        $productActivePrice->getNetPrice(true),
                        $calculatedQuantity,
                        $productActivePrice->getVat(true)
                    );

                    TaxManager::addMoneyValueToNettoRes($productActivePrice->getVat(true), $productSetProductValueNetto, $cartItemTotalRes);

                    //Base
                    $productSetProductBaseValueNetto = $this->priceHelper->calculateMoneyNetValue(
                        $productActivePrice->getBaseNetPrice(true),
                        $calculatedQuantity
                    );

                    $productSetProductBaseValueGross = $this->priceHelper->calculateMoneyGrossValueFromNetPrice(
                        $productActivePrice->getBaseNetPrice(true),
                        $calculatedQuantity,
                        $productActivePrice->getVat(true)
                    );

                    TaxManager::addMoneyValueToNettoRes($productActivePrice->getVat(true), $productSetProductBaseValueNetto, $cartItemBaseTotalRes);
                }
            }

            //Wyliczamy sumaryczn?? warto???? pozycji na podstawie sk??adowych
            //zaokr??glamy na samym ko??cu
            if ($this->dataCartComponent->getPs()->get('cart.calculation.gross')) {
                //TODO przerobi?? na money
                [$valueNet, $valueGross] = TaxManager::calculateMoneyTotalNettoAndGrossFromGrossRes($cartItem->getCart()->getCurrencyIsoCode(), $cartItemTotalRes, $this->dataCartComponent->addTax($cartItem->getCart()));
                [$baseValueNetto, $baseValueGross] = TaxManager::calculateMoneyTotalNettoAndGrossFromGrossRes($cartItem->getCart()->getCurrencyIsoCode(), $cartItemTotalRes, $this->dataCartComponent->addTax($cartItem->getCart()));
            } else {
                //TODO przerobi?? na money
                [$valueNet, $valueGross] = TaxManager::calculateMOneyTotalNettoAndGrossFromNettoRes($cartItem->getCart()->getCurrencyIsoCode(), $cartItemTotalRes, $this->dataCartComponent->addTax($cartItem->getCart()));
                [$baseValueNetto, $baseValueGross] = TaxManager::calculateMoneyTotalNettoAndGrossFromNettoRes($cartItem->getCart()->getCurrencyIsoCode(), $cartItemTotalRes, $this->dataCartComponent->addTax($cartItem->getCart()));
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
            ->setQuantity($cartItem->getQuantity(true))
            ->setCalculatedAt(new \DateTime('now'))
            ->setActivePrice($setActivePrice ? $activePrice : null)
            ->setCurrencyIsoCode((string)$activePrice->getCurrencyIsoCode())
            ->setIsProductSet($isProductSet)
            ->setProductSetProductActivePrices($productSetProductActivePrices);

        //Dodatkowo w celach historycznych i kontrolnych przepisujemy wyliczone warto??ci do encji
        $cartItemSummary = $cartItem->getCartItemSummary();

        //Niestety wyst??puje rozbie??no???? w konwencji nazw kolumn
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
     * @throws \Exception
     */
//    protected function calculateActiveValues(
//        CartItem $selectedCartItem,
//        Price    $activePrice,
//        array    &$totalRes,
//        array    &$spreadRes,
//        ?float   $catalogueValueNetto,
//        ?float   $catalogueValueGross
//    ): void {
//        $vat = $activePrice->getVat(true);
//
//        if ($this->dataCartComponent->getPs()->get('cart.calculation.gross')) {
//            $valueNetto = $this->priceHelper->calculateMoneyNetValueFromGrossPrice(
//                $activePrice->getGrossPrice(true),
//                $selectedCartItem->getQuantity(true),
//                $activePrice->getVat()
//            );
//
//            $valueGross = $this->priceHelper->calculateMoneyGrossValue(
//                $activePrice->getGrossPrice(true),
//                $selectedCartItem->getQuantity(true)
//            );
//
//            TaxManager::addMoneyValueToGrossRes($vat, $valueGross, $totalRes);
//            if ($catalogueValueGross !== null) {
//                TaxManager::addMoneyValueToGrossRes($vat, ($catalogueValueGross > $valueGross) ? $catalogueValueGross - $valueGross : 0, $spreadRes);
//            }
//        } else {
//            $valueNetto = $this->priceHelper->calculateNetValue($activePrice->getNetPrice(), $selectedCartItem->getQuantity());
//            $valueGross = $this->priceHelper->calculateGrossValueFromNetPrice($activePrice->getNetPrice(), $selectedCartItem->getQuantity(), $activePrice->getVat());
//
//            TaxManager::addValueToNettoRes($vat, $valueNetto, $totalRes);
//            if ($catalogueValueNetto !== null) {
//                TaxManager::addValueToNettoRes($vat, ($catalogueValueNetto > $valueNetto) ? $catalogueValueNetto - $valueNetto : 0, $spreadRes);
//            }
//        }
//    }

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

        $quantity = $cartItemRequestProductData->getQuantity() ?: ValueHelper::createValueZero();
        $isSelected = $cartItemRequestProductData->isSelected();

        if ($quantity !== null && $quantity->lessThanOrEqual(ValueHelper::createValueZero())) {

            //TODO refactor voters
//            if (!$merge && !$this->dataCartComponent->getAuthorizationChecker()->isGranted(CartVoterInterface::ACTION_REMOVE_CART_ITEMS, $existingCartItem->getCart())) {
//                throw new \Symfony\Component\Security\Core\Exception\AccessDeniedException();
//            }

            //usuwamy pozycj??
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

        } elseif ($quantity !== null && $quantity->greaterThan(ValueHelper::createValueZero()) && $increaseQuantity) {
//            if (!$merge && !$this->dataCartComponent->getAuthorizationChecker()->isGranted(CartVoterInterface::ACTION_EDIT_CART_ITEMS, $existingCartItem->getCart())) {
//                throw new \Symfony\Component\Security\Core\Exception\AccessDeniedException();
//            }

            $existingCartItem->increaseQuantity($cartItemRequestProductData->getQuantity(true));
            $update = true;
        } elseif ($quantity !== null && $quantity->greaterThan(ValueHelper::createValueZero())) {
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
     * Przekazujemy tablic??
     * [
     *  $productId => $quantity
     * ]
     *
     * Dodaje lub uaktualnia pozycj?? w koszyku na podstawie productDetail i ilo??ci
     * $updateAllCartItems - argument pozwala wymusi?? przeliczenie wszystkich pozycji w koszyku, je??eli wyst??puje zale??no???? pomi??dzy zmian?? jeden pozycji, a pozosta??ymi
     * Przeliczanie wszystkich pozycji nale??y stosowa?? wy????cznie przy obs??udz?? zmian pozycji z poziomu widoku kroku 1 koszyka
     * Dodaj??c lub zwi??kszaj??c ilo???? dla danej pozycji z poziomu widoku produktu nie ma potrzeby przeliczania pozosta??ych pozycji
     *
     * @param Cart|null $cart
     * @param array $updateData
     * @param bool $increaseQuantity
     * @param bool $updateAllCartItems
     * @param bool $fromOrderTemplate
     * @param bool $merge
     * @return CartItemUpdateResult
     * @throws \Exception
     */
    public function updateCartItems(
        CartInterface $cart = null,
        array         $updateData,
        bool          $increaseQuantity = false,
        bool          $updateAllCartItems = false,
        bool          $fromOrderTemplate = false,
        bool          $merge = false
    ): CartItemUpdateResult {
        //Weryfikacja uprawnie?? powinna odby?? si?? z poziomu modu??u
        $updateResult = new CartItemUpdateResult();
        //Potrzebujemy kluczy int, reindexujemy tablic??
        $updateData = array_values($updateData);
        $updateData = $this->cartItemCartComponent->prepareCartItemsUpdateDataCollection($updateData, $updateResult);
        [$productIds, $orderCodes] = $this->cartItemCartComponent->prepareCartItemsUpdateDataArray($updateData, $updateResult);

        //Je??eli request nie zawiera kompletnych danych wyrzucamy exception
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

                    //Informacja o aktualizacji ilo??ci sztuk uzale??niona jest od parametru
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

        // Niestniej??ce pozycje traktujemy jako nowe,
        // weryfikujemy grupowo czy wskazane detailsy istniej?? faktycznie w bazie, otrzymyjemy tylko zweryfikowane ID produktu
        // je??eli details jest aktywny i spelnia warunki filtrowania produktu!
        //Je??eli mamy do czynienia z zestawem produkt??w uzupe??niamy zawarto???? listy

        $fetchedProductUuids = $this->dataCartComponent->getProductManager()->getRepository()->checkEnabledProductUuids(
            $productIds
        );

        //weryfikujemy, kt??re product details s?? dost??pne dla u??ytkownika EDI - wsparcie pod k??tem pierwszego wydania
        $availableProductsForCustomer = [];

        if (!$this->dataCartComponent->getPs()->get('cart.products.all_available_for_user') && $cart->getBillingContractor()) {
            //Pul?? produkt??w do weryfikacji uzupe??niamy o ID produkt??w wchodz??cych w sk??ad zestaw??w
            //Na podstawie ID produkt??w przekazanych do weryfikacji, pobieramy ID zestaw??w

            $productSetIds = $this->dataCartComponent->getProductManager()->getRepository()->getProductSetsIdsByProductUuids(array_values($productIds));
            $productSetProductUuids = $this->dataCartComponent->getProductSetProductManager()->getRepository()->getProductSetProductIds($productSetIds, true);

//            $availableProductsForCustomer = $this->customerAvailabilityManager->areProductsAvailableForCustomerByUuids(
//                array_merge($productIds, $productSetProductUuids),
//                $cart->getCustomer(),
//                $this->applicationManager->getApplication()
//            );

            $availableProductsForCustomer = [];
        }

        //Dane produkt??w, kt??re wcze??niej nie istnia??y w koszyku
        //Konieczne jest utworzone nowych pozycji


        if (count($updateData)) {
            /** @var array $orderCodeDetailDataRow */

            foreach ($updateData->getCollection() as $key => $orderCodeDetailDataRow) {
                /**
                 * @var CartItemRequestProductData $productDataRow
                 */
                foreach ($orderCodeDetailDataRow as $productDataRow) {

                    //Pomijamy przetworzone wcze??niej pozycje
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


        //Aktualizacja pozycji w bazie, od??wie??enie obiektu koszyka
        $this->dataCartComponent->getCartManager()->flush();
        //TODO implement in ObjectManager

        //$this->dataCartComponent->getCartManager()->refresh($cart);

        if ($updateAllCartItems) {
            //Przeliczenie wszystkich pozycji i konwersja do tablicy -
            $processedItemsData = $this->rebuildAndProcessCartItems($cart);
        }

        //Flush jest niezb??dny
        $this->dataCartComponent->getCartManager()->flush();
        //Dodane w w celu weryfikacji problemu problemy z rozbie??no??ci?? pomi??dzy cartItems na packageItem
        // $this->dataCartComponent->getCartManager()->refresh($cart);

        //Sprawdzamy domy??lny spos??b podzia??u paczki - realizowane w ramach updatePackages
        $this->packageSplitCartComponent->updatePackages($cart);

        //Aktualizacja cen wszystkich dost??pnych pozycjach cartItems
        return $updateResult;
    }

    /**
     * Sprawdza aktualne stany magazynowe i koryguje ilo???? w koszyku, zwraca informacje o zamianach w postaci tablicy notyfikacji
     *
     * @param Cart $cart
     * @return array
     * @throws \Exception
     */
    public function checkStorage(Cart $cart): array
    {
        $selectedCartItems = $cart->getSelectedCartItems();

        //przygotowujemy notyfikacje jezeli stan magazynowy jest mniejszy od ????danego
        $notifications = [];
        //TODO docelowo do przygotowania metodka wyci??gaj??ca stany produkt??w, grupowo do tablicy

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
            //UWAGA! Sprawdzamy czy kt??ra?? z pozycji nie zosta???? usuni??ta, w przypadku wy????czenia softdelete nale??y sprawdzi?? id obiektu
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
     * Usuni??cie produkt??w niedost??pnych dla p??atnika/u??ytkownika
     * Metoda weryfikuje flagi, ustawienia ProductDetails etc. w tym zestaw??w i flag sk??adowych.
     * Nie nast??puje tutaj weryfikacja cen
     *
     * @param Cart $cart
     * @return bool
     */
    public function removeUnavailableProducts(Cart $cart): bool
    {
        $cartItemRemoved = false;

        //Usuwanie produkt??w z koszyka, kt??re przesta??y by?? dost??pne dla u??ytkownika

        $productIds = [];

        /**
         * @var CartItemInterface $cartItem
         */
        foreach ($cart->getCartItems() as $cartItem) {
            $productIds[] = $cartItem->getProduct()->getId();
        }


        // Sprawdzamy czy produkty s?? dost??pne dla tego klienta
        /**
         * @var CartItem $cartItem
         */
        foreach ($cart->getCartItems() as $cartItem) {
            //Check each CartItem
        }

        if ($cartItemRemoved) {
            $this->cartManager->flush();
        }

        return $cartItemRemoved;
    }

    /**
     * Pobiera tablic?? z typami pozycji, kt??re zosta??o zaktualizowane podczas obecnego requesta
     * @depracted
     *
     * @return array
     */
    public function getCartItemTypeUpdated()
    {
        return $this->cartItemTypeUpdated;
    }

    /**
     * Weryfikacja obecno??ci ordercode przy dodawaniu pozycji do koszyka
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
     * Przygotowanie danych przed renderowaniem modu??u
     *
     * @param Cart $cart
     * @throws \Exception
     */
    public function prepare(CartInterface $cart)
    {
        $this->validateDependencies($cart);
    }

    /**
     * @throws \Exception
     */
    public function checkQuantityAndPriceForCartItem(
        CartItem                   $cartItem,
        CartItemRequestProductData $productDataRow
    ): CartItem {
        return $this->cartItemCartComponent->checkQuantityAndPriceForCartItem(
            $cartItem,
            $productDataRow
        );
    }
}
