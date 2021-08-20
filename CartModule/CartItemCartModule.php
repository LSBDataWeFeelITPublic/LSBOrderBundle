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
use LSB\OrderBundle\Model\CartItemRequestProductData;
use LSB\PricelistBundle\Model\Price;
use LSB\ProductBundle\Entity\Product;
use LSB\ProductBundle\Entity\ProductInterface;
use LSB\ProductBundle\Entity\ProductSetProduct;
use LSB\ProductBundle\Entity\Storage;
use LSB\UtilityBundle\Value\Value;
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
        protected DataCartComponent     $dataCartComponent,
        protected CartItemCartComponent $cartItemCartComponent
    ) {
        parent::__construct();
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
        $this->dataCartComponent->getCartSummary($cart, true);
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

        [$itemsCnt, $itemsData, $notifications, $alertMessages, $cart] = $this->updateCartItems(
            $cart,
            $productsData,
            $increaseQuantity,
            $fetchAllCartItems
        );

        $this->validateDependencies($cart);

        return $this->getResponse($cart, $itemsData, $alertMessages);
    }

    /**
     * Przygotowanie odpowiedzi na request zmiany pozycji
     *
     * @inheritDoc
     */
    public function getResponse(Cart $cart, array $itemsData, array $alertMessages)
    {
        return [
            'alertMessages' => $alertMessages,
            'items' => $itemsData,
            'redirectToCart' => $this->checkForRedirectToCart($itemsData),
        ];
    }

    /**
     * Weryfikacja warunku przekierowania do koszyka po zmianie stanu listy pozycji
     *
     * @param array $itemsData
     * @return bool
     */
    protected function checkForRedirectToCart(array $itemsData)
    {
        if (!$this->dataCartComponent->getPs()->get('cart.redirect_to_cart_after_buy')) {
            return false;
        } elseif ($this->dataCartComponent->getPs()->get('cart.redirect_to_cart_after_buy')
            && count($itemsData) && array_key_exists('directlyProcessedCnt', $itemsData)
            && (
                $itemsData['directlyProcessedCnt'] && array_key_exists(
                    'created',
                    $itemsData['directlyProcessedCnt']
                ) && $itemsData['directlyProcessedCnt']['created'] > 0
                || $itemsData['directlyProcessedCnt'] && array_key_exists(
                    'updated',
                    $itemsData['directlyProcessedCnt']
                ) && $itemsData['directlyProcessedCnt']['updated'] > 0
            )) {
            return true;
        }

        return false;
    }

    /**
     * Metoda wstrzykuje ceny do pozycji koszyka
     *
     * @param Cart $cart
     * @param bool $setActivePrice
     * @throws \Exception
     */
    public function injectPricesToCartItems(CartInterface $cart, bool $setActivePrice = true): void
    {
        //przypisujemy aktualne ceny netto i brutto - do ewentualnego usprawnienia pod kątem wydajności

        $cartItems = $cart->getCartItems();
        $productSetProductActivePrices = [];

        /**
         * @var CartItem $cartItem
         */
        foreach ($cartItems as $cartItem) {
            if ($cartItem->getCartItemSummary() && $cartItem->getCartItemSummary()->getCalculatedAt()) {
                continue;
            }

            $activePrice = $this->cartItemCartComponent->getPriceForCartItem($cartItem);

            $isProductSet = $cartItem->getProduct() && $cartItem->getProduct()->isProductSet() ?? false;

            if (!$isProductSet) {
                //Wartość pozycji koszyka liczona jest na tym etapie. Bazując na jednostkowych cenach
                if ($this->dataCartComponent->getPs()->get('cart.calculation.gross')) {
                    $valueNetto = $this->dataCartComponent->calculateNettoValueFromGross(
                        $activePrice->getGrossPrice(),
                        $cartItem->getQuantity(),
                        $activePrice->getVat()
                    );
                    $valueGross = $this->dataCartComponent->calculateGrossValue(
                        $activePrice->getGrossPrice(),
                        $cartItem->getQuantity()
                    );

                    $baseValueNetto = $this->dataCartComponent->calculateNettoValueFromGross(
                        $activePrice->getBaseGrossPrice(),
                        $cartItem->getQuantity(),
                        $activePrice->getVat()
                    );
                    $baseValueGross = $this->dataCartComponent->calculateGrossValue(
                        $activePrice->getBaseGrossPrice(),
                        $cartItem->getQuantity()
                    );
                } else {
                    $valueNetto = $this->dataCartComponent->calculateNettoValue(
                        $activePrice->getNetPrice(),
                        $cartItem->getQuantity()
                    );
                    $valueGross = $this->dataCartComponent->calculateGrossValueFromNetto(
                        $activePrice->getNetPrice(),
                        $cartItem->getQuantity(),
                        $activePrice->getVat()
                    );

                    $baseValueNetto = $this->dataCartComponent->calculateNettoValue(
                        $activePrice->getBaseNetPrice(),
                        $cartItem->getQuantity()
                    );
                    $baseValueGross = $this->dataCartComponent->calculateGrossValueFromNetto(
                        $activePrice->getBaseNetPrice(),
                        $cartItem->getQuantity(),
                        $activePrice->getVat()
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
                    $productQuantity = $productSetProduct->getQuantity();
                    $productSet = $cartItem->getProduct();
                    $calculatedQuantity = $cartItem->getQuantity() * $productQuantity;
                    $productActivePrice = $this->cartItemCartComponent->getPriceForProduct($cartItem->getCart(), $product, $productSet, $calculatedQuantity);

                    //Sumaryczna wycena składnika
                    $productSetProductActivePrices[$product->getId()] = $productActivePrice;


                    if ($this->dataCartComponent->getPs()->get('cart.calculation.gross')) {
                        $productSetProductValueNetto = $this->dataCartComponent->calculateNettoValueFromGross($productActivePrice->getGrossPrice(), $calculatedQuantity, $productActivePrice->getVat());
                        $productSetProductValueGross = $this->dataCartComponent->calculateGrossValue($activePrice->getGrossPrice(), $calculatedQuantity);
                        TaxManager::addValueToGrossRes($productActivePrice->getVat(), $productSetProductValueGross, $cartItemTotalRes);

                        //Base
                        $productSetProductBaseValueNetto = $this->dataCartComponent->calculateNettoValueFromGross($productActivePrice->getBaseGrossPrice(), $calculatedQuantity, $productActivePrice->getVat());
                        $productSetProductBaseValueGross = $this->dataCartComponent->calculateGrossValue($activePrice->getBaseGrossPrice(), $calculatedQuantity);
                        TaxManager::addValueToGrossRes($productActivePrice->getVat(), $productSetProductBaseValueGross, $cartItemBaseTotalRes);
                    } else {
                        $productSetProductValueNetto = $this->dataCartComponent->calculateNettoValue($productActivePrice->getNetPrice(), $calculatedQuantity);
                        $productSetProductValueGross = $this->dataCartComponent->calculateGrossValueFromNetto($productActivePrice->getNetPrice(), $calculatedQuantity, $productActivePrice->getVat());
                        TaxManager::addValueToNettoRes($productActivePrice->getVat(), $productSetProductValueNetto, $cartItemTotalRes);

                        //Base
                        $productSetProductBaseValueNetto = $this->dataCartComponent->calculateNettoValue($productActivePrice->getBaseNetPrice(), $calculatedQuantity);
                        $productSetProductBaseValueGross = $this->dataCartComponent->calculateGrossValueFromNetto($productActivePrice->getBaseNetPrice(), $calculatedQuantity, $productActivePrice->getVat());
                        TaxManager::addValueToNettoRes($productActivePrice->getVat(), $productSetProductBaseValueNetto, $cartItemBaseTotalRes);
                    }
                }

                //Wyliczamy sumaryczną wartość pozycji na podstawie składowych
                //zaokrąglamy na samym końcu
                if ($this->dataCartComponent->getPs()->get('cart.calculation.gross')) {
                    [$valueNetto, $valueGross] = TaxManager::calculateTotalNettoAndGrossFromGrossRes($cartItemTotalRes, $this->dataCartComponent->addTax($cartItem->getCart()));
                    [$baseValueNetto, $baseValueGross] = TaxManager::calculateTotalNettoAndGrossFromGrossRes($cartItemBaseTotalRes, $this->dataCartComponent->addTax($cartItem->getCart()));
                } else {
                    [$valueNetto, $valueGross] = TaxManager::calculateTotalNettoAndGrossFromNettoRes($cartItemTotalRes, $this->dataCartComponent->addTax($cartItem->getCart()));
                    [$baseValueNetto, $baseValueGross] = TaxManager::calculateTotalNettoAndGrossFromNettoRes($cartItemBaseTotalRes, $this->dataCartComponent->addTax($cartItem->getCart()));
                }
            }

            $cartItem
                ->getCartItemSummary()
                ->setPriceNetto($activePrice->getNetPrice())
                ->setPriceGross($activePrice->getGrossPrice())
                ->setBasePriceNetto($activePrice->getBaseNetPrice())
                ->setBasePriceGross($activePrice->getBaseGrossPrice())
                ->setValueNetto($valueNetto)
                ->setValueGross($valueGross)
                ->setBaseValueNetto($baseValueNetto)
                ->setBaseValueGross($baseValueGross)
                ->setTaxValue(round($valueGross - $valueNetto, 2))
                //->setTax($this->dataCartComponent->addTax($cart) ? $activePrice->getVat() : null)
                //->setRes($activePrice->getProcedureResult())
                ->setQuantity($cartItem->getQuantity())
                ->setCalculatedAt(new \DateTime('now'))
                ->setActivePrice($setActivePrice ? $activePrice : null)
                ->setCurrencyIsoCode((string)$activePrice->getCurrencyCode())
                ->setIsProductSet($isProductSet)
                ->setProductSetProductActivePrices($productSetProductActivePrices);

            //Dodatkowo w celach historycznych i kontrolnych przepisujemy wyliczone wartości do encji
            $cartItemSummary = $cartItem->getCartItemSummary();

            //Niestety występuje rozbieżność w konwencji nazw kolumn
            $cartItem
                ->setPriceNet($cartItemSummary->getPriceNetto())
                ->setValueNet($cartItemSummary->getValueNetto())
                ->setPriceGross($cartItemSummary->getPriceGross())
                ->setValueGross($cartItemSummary->getValueGross());
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
            $valueNetto = $this->dataCartComponent->calculateNettoValueFromGross($activePrice->getGrossPrice(), $selectedCartItem->getQuantity(), $activePrice->getVat());
            $valueGross = $this->dataCartComponent->calculateGrossValue($activePrice->getGrossPrice(), $selectedCartItem->getQuantity());

            TaxManager::addValueToGrossRes($vat, $valueGross, $totalRes);
            if ($catalogueValueGross !== null) {
                TaxManager::addValueToGrossRes($vat, ($catalogueValueGross > $valueGross) ? $catalogueValueGross - $valueGross : 0, $spreadRes);
            }
        } else {
            $valueNetto = $this->dataCartComponent->calculateNettoValue($activePrice->getNetPrice(), $selectedCartItem->getQuantity());
            $valueGross = $this->dataCartComponent->calculateGrossValueFromNetto($activePrice->getNetPrice(), $selectedCartItem->getQuantity(), $activePrice->getVat());

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
     */
    public function processCartItemResult(CartItem $cartItem, bool $fetchPrice = true): CartItem
    {
        $user = $this->getUser();
        $cart = $cartItem->getCart();

        if ($fetchPrice) {
            $activePrice = $this->dataCartComponent->getPricelistManager()->getPriceForProduct(
                $cartItem->getProduct(),
                null,
                null,
                $cart->getCurrency(),
                $user->getDefaultBillingContractor()?->getCurrency()

            //TODO wprowadzic relacje do waluty na poziomie contractora i addTax przy wyliczaniu cen
//                [
//                    'customer' => $user && $user->getDefaultCustomer() ? $user->getDefaultCustomer() : null,
//                    'currency' => $cart->getCurrencyRelation() ? $cart->getCurrencyRelation() : null,
//                    'quantity' => $cartItem->getQuantity(),
//                    'addTax' => $this->dataCartComponent->addTax($cart),
//                ]
            );

            //increase total
            if ($this->dataCartComponent->getPs()->get('cart.calculation.gross')) {
                $valueNetto = $this->dataCartComponent->calculateNettoValueFromGross(
                    $activePrice->getGrossPrice(),
                    $cartItem->getQuantity(),
                    $activePrice->getVat()
                );
                $valueGross = $this->dataCartComponent->calculateGrossValue(
                    $activePrice->getGrossPrice(),
                    $cartItem->getQuantity()
                );

                $baseValueNetto = $this->dataCartComponent->calculateNettoValueFromGross(
                    $activePrice->getBaseGrossPrice(),
                    $cartItem->getQuantity(),
                    $activePrice->getVat()
                );
                $baseValueGross = $this->dataCartComponent->calculateGrossValue(
                    $activePrice->getBaseGrossPrice(),
                    $cartItem->getQuantity()
                );
            } else {
                $valueNetto = $this->dataCartComponent->calculateNettoValue(
                    $activePrice->getNetPrice(),
                    $cartItem->getQuantity()
                );
                $valueGross = $this->dataCartComponent->calculateGrossValueFromNetto(
                    $activePrice->getNetPrice(),
                    $cartItem->getQuantity(),
                    $activePrice->getVat()
                );

                $baseValueNetto = $this->dataCartComponent->calculateNettoValue(
                    $activePrice->getBaseNetPrice(),
                    $cartItem->getQuantity()
                );
                $baseValueGross = $this->dataCartComponent->calculateGrossValueFromNetto(
                    $activePrice->getBaseNetPrice(),
                    $cartItem->getQuantity(),
                    $activePrice->getVat()
                );
            }

            $cartItem
                ->getCartItemSummary()
                ->setPriceNetto($activePrice->getNetPrice())
                ->setPriceGross($activePrice->getGrossPrice())
                ->setBasePriceNetto($activePrice->getBaseNetPrice())
                ->setBasePriceGross($activePrice->getBaseGrossPrice())
                ->setValueNetto($valueNetto)
                ->setValueGross($valueGross)
                ->setBaseValueNetto($baseValueNetto)
                ->setBaseValueGross($baseValueGross)
                ->setTaxValue(round($valueGross - $valueNetto, 2));
        }

        return $cartItem;
    }

    /**
     * @param CartItem $existingCartItem
     * @param float|null $quantity
     * @param bool $increaseQuantity
     * @param array $itemsCnt
     * @param bool $update
     * @param bool $isSelected
     * @param bool $isSelectedForOption
     * @param array $removedItemsIds
     * @param bool $merge
     * @return bool
     */
    protected function processQuantityForExistingCartItem(
        CartItem $existingCartItem,
        ?float   $quantity,
        bool     $increaseQuantity,
        array    &$itemsCnt,
        bool     $update,
        ?bool    $isSelected,
        ?bool    $isSelectedForOption,
        array    &$removedItemsIds,
        bool     $merge = false
    ): bool {
        if ($quantity !== null && $quantity <= 0) {

            //Sprawdzamy uprawnienia do usunięcia pozycji
//            if (!$merge && !$this->dataCartComponent->getAuthorizationChecker()->isGranted(CartVoterInterface::ACTION_REMOVE_CART_ITEMS, $existingCartItem->getCart())) {
//                throw new \Symfony\Component\Security\Core\Exception\AccessDeniedException();
//            }

            //usuwamy pozycję
            $removedItemsIds[] = (string)$existingCartItem->getUuid();
            $this->dataCartComponent->getCartItemManager()->remove($existingCartItem);

            $itemsCnt['removed']++;
            $alertMessages[self::ALERT_MESSAGE_WARNING][] = $this->dataCartComponent->getTranslator()->trans(
                'Cart.Module.CartItems.AlertMessage.ProductRemovedFromCart',
                [
                    '%productName%' => $this->dataCartComponent->getPs()->get(
                        'cart.showProductOrderCodeInMessage'
                    ) ? $existingCartItem->getOrderCode() : $existingCartItem->getProduct()->getName(),
                    '%quantity%' => ($existingCartItem->getQuantity()),
                ],
                'Cart'
            );
        } elseif ($quantity !== null && $quantity > 0 && $increaseQuantity) {
//            if (!$merge && !$this->dataCartComponent->getAuthorizationChecker()->isGranted(CartVoterInterface::ACTION_EDIT_CART_ITEMS, $existingCartItem->getCart())) {
//                throw new \Symfony\Component\Security\Core\Exception\AccessDeniedException();
//            }

            $existingCartItem->increaseQuantity($quantity);
            $update = true;
        } elseif ($quantity !== null && $quantity > 0) {
//            if (!$merge && !$this->dataCartComponent->getAuthorizationChecker()->isGranted(CartVoter::ACTION_EDIT_CART_ITEMS, $existingCartItem->getCart())) {
//                throw new \Symfony\Component\Security\Core\Exception\AccessDeniedException();
//            }

            $existingCartItem->setQuantity($quantity);
            $update = true;
        }

        //aktualizacja wybrania flagi pozycji
        if ($isSelected !== null) {
            //mamy true or false
            $existingCartItem->setIsSelected($isSelected);
            $update = true;
        }

        //aktualizacja wybrania flagi wyboru opcji do pozycji - wymaga aby opcja była już wybrana?
        if ($isSelectedForOption !== null) {
            //mamy true or false
            $existingCartItem->setIsSelectedForOption($isSelectedForOption);
            if ($isSelectedForOption === false) {
                $existingCartItem->setIsSelected(false);
            }
            $update = true;
        }

//        //Weryfikacja ustawień
//        if ($existingCartItem->getIsSelectedForOption() && $existingCartItem->getIsSelected() && $existingCartItem->getSelectedOption() === null) {
//            //Działa zakomentowuje do testów - być może to powinno być w module cartItem?
//            //$existingCartItem->setIsSelected(false);
//        }

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
     * @return array
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Exception
     */
    public function updateCartItems(
        CartInterface $cart = null,
        array         $updateData,
        bool          $increaseQuantity = false,
        bool          $updateAllCartItems = false,
        bool          $fromOrderTemplate = false,
        bool          $merge = false
    ) {
        //Weryfikacja uprawnień powinna odbyć się z poziomu modułu

        //Potrzebujemy kluczy int, reindexujemy tablicę
        $updateData = array_values($updateData);

        $itemsCnt = [
            static::ITEMS_CNT_CREATED => 0,
            static::ITEMS_CNT_UPDATED => 0,
            static::ITEMS_CNT_REMOVED => 0,
            static::ITEMS_CNT_SKIPPED => 0
        ];

        //At this moment updateData array should be processed to a CartItemRequestCollection
        $updateData = $this->cartItemCartComponent->prepareCartItemsUpdateDataCollection($updateData, $itemsCnt);


        $updatedItemsArray = $createdItemsArray = $removedItemsIds = $skippedItemsArray = [];
        $processedItemsData = [];


        [$productData, $productIds, $orderCodes, $skippedItemsArray] = $this->cartItemCartComponent->prepareCartItemsUpdateDataArray($updateData, $itemsCnt);

        //Jeżeli request nie zawiera kompletnych danych wyrzucamy exception
        if (count($productIds) == 0) {
            throw new \Exception('Missing update data for cart items');
        }

        $notifications = [];
        $alertMessages = [];
        $update = false;

        //Przetworzenie, aktualizacja istniejących już pozycji w koszyku
        if ($cart->getId()) {
            $existingCartItems = $this->dataCartComponent->getCartItemManager()->getRepository()->getCartItemsByCartProductAndOrderCodes(
                $cart->getId(),
                $updateData
            );
        } else {
            $existingCartItems = [];
        }

        if (count($existingCartItems)) {

            /**
             * @var CartItem $existingCartItem
             */
            foreach ($existingCartItems as $existingCartItem) {
                //Zapisanie typów podlegających aktualizacji
                $this->cartItemTypeUpdated[$existingCartItem->getProduct()->getType()] = true;
                $orderCode = $existingCartItem->getOrderCode() ? $existingCartItem->getOrderCode() : CartItem::DEFAULT_ORDER_CODE_VALUE;

                if (array_key_exists((string)$existingCartItem->getProduct()->getUuid(), $productData)
                    && array_key_exists($orderCode, $productData[(string)$existingCartItem->getProduct()->getUuid()])
                ) {
                    $row = $productData[(string)$existingCartItem->getProduct()->getUuid()][$orderCode];
                    $quantity = array_key_exists('quantity', $row) ? $row['quantity'] : null;
                    $isSelected = array_key_exists('isSelected', $row) ? filter_var(
                        $row['isSelected'],
                        FILTER_VALIDATE_BOOLEAN
                    ) : null;
                    $isSelectedForOption = array_key_exists('isSelectedForOption', $row) ? filter_var(
                        $row['isSelectedForOption'],
                        FILTER_VALIDATE_BOOLEAN
                    ) : null;
                } else {
                    //wlaczyc exception throw w razie potrzeby, obecnie pomijamy
                    continue;
                }

                $update = $this->processQuantityForExistingCartItem(
                    $existingCartItem,
                    $quantity,
                    $increaseQuantity,
                    $itemsCnt,
                    $update,
                    $isSelected,
                    $isSelectedForOption,
                    $removedItemsIds,
                    $merge
                );

                if ($update) {
                    $this->checkQuantityAndPriceForCartItem($existingCartItem, $notifications, $doPersist = false);
                    $itemsCnt['updated']++;

                    //przyszlosciowo - aktualizujemy caly wiersz, ze wzgledu mozliwosci zmiany cen w zaleznosci od ilosci sztuk
                    $updatedItemsArray[(string)$existingCartItem->getUuid()] = $this->processCartItemResult(
                        $existingCartItem
                    );

                    //Informacja o aktualizacji ilości sztuk uzależniona jest od parametru
                    if ($quantity !== null && $this->dataCartComponent->getPs()->get('cart.notification.quantity_change.show')) {
                        $alertMessages[self::ALERT_MESSAGE_SUCCESS][] = $this->dataCartComponent->getTranslator()->trans(
                            'Cart.Module.CartItems.AlertMessage.ProductQuantityChange',
                            [
                                '%productName%' => $this->dataCartComponent->getPs()->get(
                                    'cart.showProductOrderCodeInMessage'
                                ) ? $existingCartItem->getOrderCode() : $existingCartItem->getProduct()->getName(),
                                '%quantity%' => ($existingCartItem->getQuantity()),
                            ],
                            'Cart'
                        );
                    }
                }

                //usuwamy pozycję z tablicy
                unset(
                    $productData[(string)$existingCartItem->getProduct()->getUuid()][$existingCartItem->getOrderCode() ? $existingCartItem->getOrderCode() : CartItem::DEFAULT_ORDER_CODE_VALUE]
                );
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
//                    $quantity = array_key_exists('quantity', $productDataRow) ? $productDataRow['quantity'] : null;
//                    $productUuid = array_key_exists('uuid', $productDataRow) ? $productDataRow['uuid'] : null;
//                    $orderCode = array_key_exists('ordercode', $productDataRow) ? $productDataRow['ordercode'] : null;
//                    $productSetUuid = array_key_exists('productSetUuid', $productDataRow) ? $productDataRow['productSetUuid'] : null;
//                    $productSetQuantity = array_key_exists('productSetQuantity', $productDataRow) ? $productDataRow['productSetQuantity'] : null;

                    //Metoda weryfikująca możliwość utworzenia nowej pozycji w koszyku - działa niezależenie od mechanizmów sprawdzania

                    if (!$this->cartItemCartComponent->canCreateNewCartItem(
                        $cart,
                        $productDataRow,
                        $fetchedProductUuids,
                        $availableProductsForCustomer,
                        $fromOrderTemplate,
                        true,
                        $alertMessages
                    )) {
                        $skippedItemsArray[] = $productDataRow;
                        $itemsCnt['skipped']++;
                        continue;
                    }

                    $cartItem = $this->cartItemCartComponent->createNewCartItem($cart, $productDataRow);

                    $this->cartItemTypeUpdated[$cartItem->getProduct()->getType()] = true;

                    if ($cart->getSelectedDeliveryVariant() && $cart->getSuggestedDeliveryVariant() === CartInterface::DELIVERY_VARIANT_ONLY_AVAILABLE) {
                        $cart->setSelectedDeliveryVariant(false);
                    }

                    $this->cartItemCartComponent->checkQuantityAndPriceForCartItem($cartItem, $notifications);

                    $createdItemsArray[$productDataRow->getProductUuid()] = $this->processCartItemResult($cartItem);
                    $alertMessages[self::ALERT_MESSAGE_SUCCESS][] = $this->dataCartComponent->getTranslator()->trans(
                        'Cart.Module.CartItems.AlertMessage.AddedNewProductToCart',
                        [
                            '%productName%' => $this->dataCartComponent->getPs()->get(
                                'cart.showProductOrderCodeInMessage'
                            ) ? $cartItem->getOrderCode() : $cartItem->getProduct()->getName(),
                            '%quantity%' => ($cartItem->getQuantity()),
                        ],
                        'Cart'
                    );

                    $itemsCnt['created']++;
                }
            }
        }

        //Aktualizacja pozycji w bazie, odświeżenie obiektu koszyka
        $this->dataCartComponent->getCartManager()->flush();
        //TODO implement in ObjectManager
        //$this->dataCartComponent->getCartManager()->refresh($cart);

        if ($updateAllCartItems) {
            //Przeliczenie wszystkich pozycji i konwersja do tablicy -
            $processedItemsData = $this->rebuildAndProcessCartItems($cart);
        }

        //Mapowanie danych o pozycjach, TODO zamiana na obiekty
        $processedItemsData['directlyProcessedData'] = [
            'created' => $createdItemsArray,
            'updated' => $updatedItemsArray,
            'removed' => $removedItemsIds,
            'skipped' => $skippedItemsArray
        ];

        $processedItemsData['directlyProcessedCnt'] = [
            'created' => count($createdItemsArray),
            'updated' => count($updatedItemsArray),
            'removed' => count($removedItemsIds),
            'skipped' => count($skippedItemsArray)
        ];

        $processedItemsData['directlyProcessedNotifications'] = $notifications;

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

        return [
            $itemsCnt,
            $processedItemsData,
            $notifications,
            $alertMessages,
            $cart
        ];
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
     * Przetwarza zawartość pozycji koszyka, weryfikuje dostępność i zwraca wynik do array
     *
     * @param Cart|null $cart
     * @param bool $flush
     * @return array
     * @throws \Exception
     */
    public function rebuildAndProcessCartItems(Cart $cart = null, bool $flush = true): array
    {
        $this->storageManager->clearReservedQuantityArray();

        if (!$cart) {
            $cart = $this->dataCartComponent->getCart();
        }

        $cartItemsArray = [];
        $notifications = [];

        $cartItemsArray['updated'] = [];
        $cartItemsArray['removed'] = [];
        $cartItemsArray['created'] = [];

        /**
         * @var CartItem $cartItem
         */
        foreach ($cart->getItems() as $cartItem) {
            $this->dataCartComponent->checkQuantityAndPriceForCartItem($cartItem, $notifications);
            //UWAGA! Sprawdzamy czy któraś z pozycji nie zostałą usunięta, w przypadku wyłączenia softdelete należy sprawdzić id obiektu
            $entityState = $this->em->getUnitOfWork()->getEntityState($cartItem);

            if ($entityState === UnitOfWork::STATE_MANAGED) {
                $cartItemsArray['updated'][(string)$cartItem->getUuid()] = $cartItem;
            } elseif ($entityState == UnitOfWork::STATE_NEW && $cartItem->getProduct()) {
                $cartItemsArray['created']['new_' . (string)$cartItem->getProduct()->getUuid()] = $cartItem;
            } else {
                $cartItemsArray['removed'][(string)$cartItem->getUuid()] = $cartItem;
            }
        }

        if ($flush) {
            $this->em->flush();

            //Przepisanie tablicy po flushu
            if (count($cartItemsArray['created'])) {
                foreach ($cartItemsArray['created'] as $oldKey => $cartItem) {
                    $cartItemsArray['created'][(string)$cartItem->getUuid()] = $cartItem;
                    unset($cartItemsArray['created'][$oldKey]);
                }
            }
        }

        //Process to array
        //Na tym etapie, wstrzykiwanie cen jest zbędne, do usunięcia po zakończeniu refaktoringu
        //$this->injectPricesToCartItems($cart);

        foreach ($cartItemsArray['created'] as $key => $cartItem) {
            $cartItemsArray['created'][$key] = $this->processCartItemResult($cartItem, false);
        }

        foreach ($cartItemsArray['updated'] as $key => $cartItem) {
            $cartItemsArray['updated'][$key] = $this->processCartItemResult($cartItem, false);
        }

        foreach ($cartItemsArray['removed'] as $key => $cartItem) {
            $cartItemsArray['removed'][$key] = $key; //ID nie będzie już dostępne w tym momencie po flushu
        }

        //Pozycje w zależności od dostępności i trybu oversale mogą zostać zaktualizowane lub usunięte

        return [
            'cnt' => [
                'created' => count($cartItemsArray['created']),
                'updated' => count($cartItemsArray['updated']),
                'removed' => count($cartItemsArray['removed']),
            ],
            'data' => [
                'created' => $cartItemsArray['created'],
                'updated' => $cartItemsArray['updated'],
                'removed' => $cartItemsArray['removed'],
            ],
            'notifications' => $notifications,
        ];
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
