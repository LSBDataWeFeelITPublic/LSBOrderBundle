<?php
declare(strict_types=1);

namespace LSB\OrderBundle\CartComponent;

use JetBrains\PhpStorm\Pure;
use LSB\OrderBundle\Entity\Cart;
use LSB\OrderBundle\Entity\CartInterface;
use LSB\OrderBundle\Entity\CartItem;
use LSB\OrderBundle\Entity\CartItemInterface;
use LSB\OrderBundle\Manager\CartItemManager;
use LSB\OrderBundle\Manager\CartManager;
use LSB\OrderBundle\Model\CartItemModule\CartItemUpdateResult;
use LSB\OrderBundle\Model\CartItemModule\Notification;
use LSB\OrderBundle\Model\CartItemRequestProductData;
use LSB\OrderBundle\Model\CartItemRequestProductDataCollection;
use LSB\PricelistBundle\Manager\PricelistManager;
use LSB\PricelistBundle\Model\Price;
use LSB\ProductBundle\Entity\Product;
use LSB\ProductBundle\Entity\ProductInterface;
use LSB\ProductBundle\Entity\ProductSetProduct;
use LSB\ProductBundle\Entity\StorageInterface;
use LSB\ProductBundle\Manager\ProductManager;
use LSB\ProductBundle\Manager\StorageManager;
use LSB\ProductBundle\Service\StorageService;
use LSB\UtilityBundle\Helper\ValueHelper;
use LSB\UtilityBundle\Value\Value;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class CartItemCartComponent extends BaseCartComponent
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

    public function __construct(
        TokenStorageInterface           $tokenStorage,
        protected ProductManager        $productManager,
        protected CartManager           $cartManager,
        protected CartItemManager       $cartItemManager,
        protected ParameterBagInterface $ps,
        protected PricelistManager      $pricelistManager,
        protected TranslatorInterface   $translator,
        protected StorageManager        $storageManager,
        protected StorageService        $storageService
    ) {
        parent::__construct($tokenStorage);
    }

    /**
     * Creates new position in cart
     *
     * @param Cart $cart
     * @param CartItemRequestProductData $cartItemRequestProductData
     * @return CartItem
     * @throws \Exception
     */
    public function createNewCartItem(
        CartInterface              $cart,
        CartItemRequestProductData $cartItemRequestProductData
    ): CartItemInterface {

        if (!$cartItemRequestProductData->getProductUuid()) {
            throw new \Exception('Missing product uuid');
        }

        $productSet = null;
        $product = $this->productManager->getByUuid($cartItemRequestProductData->getProductUuid());

        if (!$product instanceof Product) {
            throw new \Exception('Missing product');
        }

        if ($cartItemRequestProductData->getProductSetUuid()) {
            $productSet = $this->productManager->getProductSetByProductAndUuid(
                $cartItemRequestProductData->getProductSetUuid(),
                $cartItemRequestProductData->getProductUuid()
            );
        }

        $cartItem = ($this->cartItemManager->createNew())
            ->setCart($cart)
            ->setProduct($product)
            ->setOrderCode($cartItemRequestProductData->getOrderCode())
            ->setQuantity($cartItemRequestProductData->getQuantity())
            ->setProductSet($productSet)
            ->setProductSetQuantity($cartItemRequestProductData->getProductSetQuantity())
            ->setCurrency($cart->getCurrency())
            ->setCurrencyIsoCode($cart->getCurrencyIsoCode());

        $this->cartItemManager->persist($cartItem);
        $cart->addCartItem($cartItem);

        //Wstrzyknięcie cen

        return $cartItem;
    }

    /**
     * Metoda sprawdza dostępność produktu do przetwarzania w koszyku na podstawie flag, dostępnej puli produktów i detailsa.
     * Weryfikacja ilości sztuk i ceny odbywa się w kolejnych procesach przetwarzania.
     *
     * @param Product $product
     * @param string|null $orderCode
     * @param array $availableProductsForCustomer
     * @param bool $useUuid
     * @return bool
     */
    protected function checkProductAvailabilityFlagsForCartProcessing(
        ProductInterface $product,
        ?string          $orderCode,
        array            &$availableProductsForCustomer,
        bool             $useUuid = false
    ): bool {

//        //Jeżeli płatnik nie ma przyedzielonych produktów, wówczas nie widzi żadnego produktu i żadnego produktu nie może dodać
//
//        if (!$this->ps->get('cart.products.all_available_for_user')) {
//            //pomijamy produkty, jeżeli nie są dostępne dla tego Customera
//            if (!array_key_exists($useUuid ? $product->getUuid() : $product->getId(), $availableProductsForCustomer)
//                || array_key_exists(
//                    $useUuid ? $product->getUuid() : $product->getId(),
//                    $availableProductsForCustomer
//                ) && $availableProductsForCustomer[$useUuid ? $product->getUuid() : $product->getId()] === false) {
//                //Pomijamy produkt
//                return false;
//            }
//        }

        if ($this->ps->get('cart.ordercode.enabled')) {
            if ($orderCode === null || strlen($orderCode) == 0) {
                return false;
            }
        }

        //Weryfikacja typu produktu
        switch ($product->getType()) {
            case ProductInterface::TYPE_SHIPPING:
                return false;
        }

        //Jeżeli mamy do czynienia z zestawem sprawdzamy czy wszystkie pozycje zestawu mają określoną dostępność flag
        if ($product->isProductSet()) {
            if ($product->getProductSetProducts()->count() <= 0) {
                return false;
            }

            /**
             * @var ProductSetProduct $productSetProduct
             */
            foreach ($product->getProductSetProducts() as $productSetProduct) {
                if (!$productSetProduct->getProduct()) {
                    continue;
                }

                if (!$this->checkProductAvailabilityFlagsForCartProcessing(
                    $productSetProduct->getProduct(),
                    null,
                    $availableProductsForCustomer,
                    $useUuid
                )) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Metoda weryfikuje możliwość dodania do koszyka
     *
     * @param Cart $cart
     * @param CartItemRequestProductData $productDataRow
     * @param array $fetchedProductUuids
     * @param array $availableProductsForCustomer
     * @param bool $fromOrderTemplate
     * @param bool $merge
     * @return bool
     * @throws \Exception
     */
    public function canCreateNewCartItem(
        Cart                       $cart,
        CartItemRequestProductData $productDataRow,
        array                      &$availableProductsForCustomer,
        bool                       $fromOrderTemplate = false,
        bool                       $merge = false
    ): bool {
//        if (!$merge && !$this->dataCartComponent->getAuthorizationChecker()->isGranted($fromOrderTemplate ? CartVoterInterface::ACTION_EDIT_CART_ITEMS : CartVoterInterface::ACTION_ADD_CART_ITEMS, $cart)) {
//            throw new \Symfony\Component\Security\Core\Exception\AccessDeniedException();
//        }

        $quantity = $productDataRow->getQuantity();
        $productUuid = $productDataRow->getProductUuid();
        $productSetUuid = $productDataRow->getProductSetUuid();
        $orderCode = $productDataRow->getOrderCode();


        if (!$productUuid) {
            $productDataRow->createErrorNotification(
                $this->translator->trans(
                    'Cart.Module.CartItems.AlertMessage.ProductNotFound',
                    ['%productUuid%' => $productUuid],
                    'Cart'
                )
            );

            return false;
        }

        if ($quantity->lessThanOrEqual(ValueHelper::createValueZero())) {
            $productDataRow->createErrorNotification(
                $this->translator->trans(
                    'Cart.Module.CartItems.AlertMessage.WrongQuantity',
                    ['%quantity%' => $quantity],
                    'Cart'
                )
            );

            return false;
        }


        //Verification of the existence of the product
        $product = $this->productManager->getByUuid($productUuid);

        if (!$product instanceof ProductInterface) {

            $productDataRow->createErrorNotification(
                $this->translator->trans(
                    'Cart.Module.CartItems.AlertMessage.ProductNotFound',
                    ['%productName%' => $product->getName()],
                    'Cart'
                )
            );

            return false;
        }

        if (!$this->checkProductAvailabilityFlagsForCartProcessing(
            $product,
            $orderCode,
            $availableProductsForCustomer,
            true
        )) {
            $productDataRow->createErrorNotification(
                $this->translator->trans(
                    'Cart.Module.CartItems.AlertMessage.ProductNotAddedToCart',
                    ['%productName%' => $product->getName()],
                    'Cart'
                )
            );
            return false;
        }

        //Sprawdzamy dostępność stanu mag. - wariant uproszczony, pełna weryfikacja magazynów po utworzeniu pozycji w koszyku
        $rawlocalQuantity = $this->getRawLocalQuantityForProduct($product);
        $rawRemoteQuantity = $this->getRawRemoteQuantityForProduct($product, $quantity);

        $totalAvailableQuantity = $rawlocalQuantity->add($rawRemoteQuantity);

        if ($this->ps->get('cart.quantity.validation.add_to_cart')
            && $totalAvailableQuantity->lessThan($productDataRow->getQuantity())
            && (!$this->isBackorderEnabled()
                || ($this->ps->get('cart.backorder.check_product_flag')
                    && !$product->isAvailableForBackorder()
                )
            )
        ) {
            $productDataRow->createErrorNotification(
                $this->translator->trans(
                    'Cart.Module.CartItems.AlertMessage.ProductNotAvailableForBackorder',
                    ['%productName%' => $product->getName()],
                    'Cart'
                )
            );

            return false;
        }

        if ($productSetUuid) {
            $productSet = $this->productManager->getProductSetByProductAndUuid($productSetUuid, $productUuid);
        } else {
            $productSet = null;
        }

        //Weryfikacja cen
        $activePrice = $this->getPriceForProduct($cart, $product, $productSet, $quantity);

        if (!$this->isZeroPriceAllowed() && (
                !$activePrice instanceof Price
                || $activePrice->getNetPrice() == 0
            )
            || ($activePrice instanceof Price && $activePrice->getNetPrice() < 0)) {

            $productDataRow->createErrorNotification(
                $this->translator->trans(
                    'Cart.Module.CartItems.AlertMessage.ProductWithZeroPriceIsNotAllowed',
                    ['%productName%' => $product->getName()],
                    'Cart'
                )
            );

            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    protected function isZeroPriceAllowed(): bool
    {
        return false;
    }

    /**
     * @param Product $product
     * @return Value
     */
    protected function getRawLocalQuantityForProduct(Product $product): Value
    {
        return $product->getLocalQuantityAvailableAtHand(true) ?? new Value(0);
    }

    /**
     * @param Product|null $product
     * @param Value|null $userQuantity
     * @return Value
     */
    protected function getRawRemoteQuantityForProduct(
        ?Product $product,
        ?Value   $userQuantity = null
    ): Value {
        return $product->getExternalQuantityAvailableAtHand(true) ?? new Value(0);
    }

    /**
     * Usunięcie produktów niedostępnych dla płatnika/użytkownika
     * Metoda weryfikuje flagi, ustawienia ProductDetails etc. w tym zestawów i flag składowych.
     * Nie następuje tutaj weryfikacja cen
     *
     * @param Cart $cart
     * @return bool
     * @throws \Exception
     */
    public function removeUnavailableProducts(Cart $cart): bool
    {
        $cartItemRemoved = false;

        /**
         * @var CartItem $cartItem
         */
        foreach ($cart->getCartItems() as $cartItem) {

            //TODO Przygotować mechanizm usuwania niedostępnych produktów
            if (1 == 0) {
                $cart->removeCartItem($cartItem);
                $cartItemRemoved = true;
            }
        }

        if ($cartItemRemoved) {
            $this->cartManager->flush();
        }

        return $cartItemRemoved;
    }

    /**
     * @param CartItem $existingCartItem
     * @param CartItemRequestProductData $cartItemRequestProductData
     * @param Value $quantity
     * @param bool $increaseQuantity
     * @param array $itemsCnt
     * @param bool $update
     * @param bool|null $isSelected
     * @param bool|null $isSelectedForOption
     * @param array $removedItemsIds
     * @param bool $merge
     * @return bool
     */
    protected function processQuantityForExistingCartItem(
        CartItem                   $existingCartItem,
        CartItemRequestProductData $cartItemRequestProductData,
        Value                      $quantity,
        bool                       $increaseQuantity,
        array                      &$itemsCnt,
        bool                       $update,
        ?bool                      $isSelected,
        ?bool                      $isSelectedForOption,
        array                      &$removedItemsIds,
        bool                       $merge = false
    ): bool {
        if ($quantity !== null && $quantity <= 0) {

            //Sprawdzamy uprawnienia do usunięcia pozycji
//            if (!$merge && !$this->dataCartComponent->getAuthorizationChecker()->isGranted(CartVoterInterface::ACTION_REMOVE_CART_ITEMS, $existingCartItem->getCart())) {
//                throw new \Symfony\Component\Security\Core\Exception\AccessDeniedException();
//            }

            //usuwamy pozycję
            $removedItemsIds[] = (string)$existingCartItem->getUuid();
            $this->cartItemManager->remove($existingCartItem);

            $itemsCnt['removed']++;

            $cartItemRequestProductData->addNotification(new Notification(
                Notification::TYPE_WARNING,
                $this->translator->trans(
                    'Cart.Module.CartItems.AlertMessage.ProductRemovedFromCart',
                    [
                        '%productName%' => $this->ps->get(
                            'cart.showProductOrderCodeInMessage'
                        ) ? $existingCartItem->getOrderCode() : $existingCartItem->getProduct()->getName(),
                        '%quantity%' => $existingCartItem->getQuantity(),
                    ],
                    'Cart'
                )
            ));

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

            $existingCartItem->setQuantity(new Value($quantity));
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
     * @param array $updateData
     * @param CartItemUpdateResult $cartItemUpdateResult
     * @return CartItemRequestProductDataCollection
     */
    public function prepareCartItemsUpdateDataCollection(
        array                &$updateData,
        CartItemUpdateResult $cartItemUpdateResult
    ): CartItemRequestProductDataCollection {
        $cartItemRequestDataCollection = new CartItemRequestProductDataCollection();

        /** @var array $row */
        foreach ($updateData as $row) {
            $quantity = null;
            $isSkipped = false;

            //Product UUID is always required, checking required fields
            if (!array_key_exists('uuid', $row)
                || (array_key_exists('uuid', $row) && !((string)$row['uuid']))
                || (array_key_exists('uuid', $row) && array_key_exists('ordercode', $row) && !($row['ordercode']) && $this->ps->get('cart.ordercode.enabled'))
            ) {
                $cartItemUpdateResult->getProcessedItems()->getUpdateCounter()->increaseSkipped();
                $isSkipped = true;
            }

            $productUuid = $row['uuid'] ?? null;

            //Uwzględnienie klucza remove
            if (isset($row['remove']) && (bool)$row['remove'] === true || !isset($row['quantity'])) {
                //Jeżeli jest ustawiona flaga remove, nadpisujemy lub dodajemy quantity z wartością 0, w celu usunięcia pozycji
                $quantity = 0;
            }

            if (array_key_exists('quantity', $row)) {
                $quantity = intval($row['quantity']);
            }

            $orderCode = null;

            if ($productUuid) {
                $productIds[$row['uuid']] = $productUuid;
            }

            if (array_key_exists('orderCode', $row)) {
                $orderCode = $row['orderCode'];
                $orderCodes[] = $orderCode;
            }

            $productSetQuantity = null;

            if (array_key_exists('productSetQuantity', $row)) {
                $productSetQuantity = $row['productSetQuantity'];
            }

            $productSetUuid = null;

            if (array_key_exists('productSetUuid', $row)) {
                $productSetQuantity = $row['productSetUuid'];
            }

            $isSelected = false;

            if (array_key_exists('isSelected', $row)) {
                $isSelected = $row['isSelected'];
            }

            $isSelectedForOption = false;

            if (array_key_exists('isSelectedForOption', $row)) {
                $isSelectedForOption = $row['isSelectedForOption'];
            }

            $item = new CartItemRequestProductData(
                $productUuid,
                $productSetUuid,
                $orderCode,
                new Value($quantity),
                new Value($productSetQuantity),
                $isSelected,
                $isSelectedForOption,
                $isSkipped ? CartItemRequestProductData::PROCESSING_TYPE_SKIPPED : null
            );

            $cartItemRequestDataCollection->add($item);
        }

        $cartItemUpdateResult->getProcessedItems()->setUpdateData($cartItemRequestDataCollection);
        return $cartItemRequestDataCollection;
    }


    /**
     * Initial processing of the data array
     *
     * @param CartItemRequestProductDataCollection $collection
     * @param CartItemUpdateResult $itemsCnt
     * @return array
     */
    #[Pure] public function prepareCartItemsUpdateDataArray(
        CartItemRequestProductDataCollection $collection,
        CartItemUpdateResult                 $itemsCnt
    ): array {
        $productIds = $orderCodes = [];

        /**
         * @var array $orderCodes
         */
        foreach ($collection->getCollection() as $productUuid => $orderCodesArray) {
            /**
             * @var CartItemRequestProductData $cartItemUpdateRequestData
             */
            foreach ($orderCodesArray as $cartItemUpdateRequestData) {
                if ($cartItemUpdateRequestData->getOrderCode()) {
                    $orderCodes[] = $cartItemUpdateRequestData->getOrderCode();
                }

                if ($cartItemUpdateRequestData->getProductUuid()) {
                    $productIds[$cartItemUpdateRequestData->getProductUuid()] = $cartItemUpdateRequestData->getProductUuid();
                }
            }
        }

        return [$productIds, $orderCodes];
    }

    /**
     * @param string|null $orderCode
     * @return bool
     */
    protected function isOrderCodeSet(?string $orderCode): bool
    {
        if (!is_null($orderCode) && trim($orderCode) != '') {
            return true;
        }

        return false;
    }

    /**
     * Ustalenie ilości sztuk i ceny
     *
     * @param CartItem $cartItem
     * @param CartItemRequestProductData $productDataRow
     * @return CartItem
     * @throws \Exception
     */
    public function checkQuantityAndPriceForCartItem(
        CartItem                   $cartItem,
        CartItemRequestProductData $productDataRow
    ): CartItem {
        $cart = $cartItem->getCart();
        $product = $cartItem->getProduct();

        if (!$product || !$cartItem->getProduct() instanceof Product) {
            throw new \Exception('Missing cart item product');
        }

        //TODO Do weryfikacji czy jest to odpowiednie miejsca na weryfikację ceny?

        if ($cartItem->getCartItemSummary() && $cartItem->getCartItemSummary()->getCalculatedAt() && $cartItem->getCartItemSummary()->getActivePrice()) {
            $activePrice = $cartItem->getCartItemSummary()->getActivePrice();
        } else {
            $activePrice = $this->getPriceForCartItem($cartItem);
        }

        if (!$this->isZeroPriceAllowed() && (!$activePrice instanceof Price || $activePrice->getNetPrice() === null || $activePrice->getNetPrice() <= 0)) {
            $cart->removeCartItem($cartItem);
            $this->cartItemManager->flush();

            return $cartItem;
        }

        //Jeżeli określono maksymalnie dostępną ilość, dokonujemy korekty na samym początku
        if ($cartItem->getProduct()->getType() === ProductInterface::TYPE_DEFAULT
            && $this->ps->get('cart.max_quantity_per_item')
            && $cartItem->getQuantity() > $this->ps->get('cart.max_quantity_per_item')) {

            $productDataRow->createDefaultNotification(
                $this->translator->trans(
                    'Cart.Module.CartItems.AlertMessage.ReachedMaxQuantityPerItem',
                    [
                        '%originalQuantity%' => $cartItem->getQuantity(),
                        '%availableQuantity%' => $this->ps->get('cart.max_quantity_per_item'),
                    ],
                    'Cart'
                ));

            $cartItem->setQuantity($this->ps->get('cart.max_quantity_per_item'));
        }

        //Pomijamy rezerwację, jeżeli pozycja nie jest wybrana na liście
        if (!$cartItem->isSelected()) {
            $productDataRow->createDefaultNotification($this->translator->trans('Cart.Module.CartItems.AlertMessage.QuantityCheckSkipped', [], 'Cart'));

            $cartItem
                ->setAvailabilityStatus(null)
                ->setTotalAvailability(null)
                ->setLocalAvailabilityStatus(null)
                ->setRemoteAvailabilityStatus(null)
                ->setBackorderAvailabilityStatus(null);

            return $cartItem;
        }

        //Jeżeli nie ma potrzeby, nie dokonujemy weryfikacji stanów magazynowych, koszyk pracuje wówczas w trybie backorder z jedną paczką

        if ($this->ps->get('cart.quantity.skip_check')) {
            $productDataRow->createDefaultNotification($this->translator->trans('Cart.Module.CartItems.AlertMessage.QuantityCheckSkipped', [], 'Cart'));

            $cartItem
                ->setAvailabilityStatus(CartItemInterface::ITEM_AVAILABLE_FOR_BACKORDER)
                ->setTotalAvailability($cartItem->getQuantity())
                ->setLocalAvailabilityStatus(null)
                ->setRemoteAvailabilityStatus(null)
                ->setBackorderAvailabilityStatus(CartItemInterface::ITEM_AVAILABLE_FOR_BACKORDER);

            return $cartItem;
        }

        $rawLocalQuantity = (int)$this->getRawLocalQuantityForProduct($product)->getAmount();
        $rawRemoteQuantity = (int)$this->getRawRemoteQuantityForProduct($product, $cartItem->getQuantity(true))->getAmount();

        //Rezerwujemy stan - uwzględnia rozdział tego samego produktu pomiędzy ordercodes
        $localQuantity = $this->storageService->checkReservedQuantity(
            $cartItem->getProduct()->getId(),
            $cartItem->getQuantity(),
            StorageInterface::TYPE_LOCAL,
            $rawLocalQuantity
        );

        $requestedRemoteQuantity = ($cartItem->getQuantity() - $localQuantity > 0) ? $cartItem->getQuantity() - $localQuantity : 0;

        //Dokonujemy rezerwacji stanów zdalnych i pominięciem uwzględniania dni dostaw i ilości mag. zewnętrznych
        $remoteQuantity = $this->storageService->checkReservedQuantity(
            $cartItem->getProduct()->getId(),
            $requestedRemoteQuantity,
            StorageInterface::TYPE_EXTERNAL,
            $rawRemoteQuantity
        );

        $remoteStoragesCountBeforeMerge = 1;
        $remoteStorages = [];


        if ($this->ps->get('cart.show_items_with_stock')) {
            $totalRemoteQuantity = $this->storageService->getAvailableRemoteQuantity($cartItem->getProduct()->getId());
            $cartItem->setTotalAvailability($rawLocalQuantity + $totalRemoteQuantity);
        }

        //Wyliczenie backorderQuantity
        $backorderQuantity = 0;
        $futureQuantity = 0;
        $totalAvailableQuantity = $localQuantity + $remoteQuantity + $futureQuantity;

        if ($this->isBackorderEnabled()
            && (!$this->ps->get('cart.backorder.check_product_flag') || $cartItem->getProduct()->isAvailableForBackorder())) {
            $backorderQuantity = ($cartItem->getQuantity() > $totalAvailableQuantity) ? $cartItem->getQuantity() - $localQuantity - $remoteQuantity : 0;
        }

        $storageCount = 0;

        $remoteStoragesCount = count($remoteStorages);

        //Z racji pominięcia wyciągania magazynów, w przypadku dostępności zewnętrznego stanu mag. uznajemy, że pochodzi z 1 magazynu
        if (!$remoteStoragesCount && $remoteQuantity > 0) {
            $storageCount++;
            $remoteStoragesCount = 1;
        }

        $storageCount = $remoteStoragesCount;

        if ($localQuantity > 0) {
            $storageCount++;
        }

        $doNotCheckAvailability = ($cartItem->getAvailability() === CartItem::ITEM_AVAILABLE_FORCED_FROM_LOCAL_STOCK) ? true : false;
        $forceUpdateAvailability = false;
        $newAvailability = null;
        $newQuantity = null;
        $remove = false;

        if ($totalAvailableQuantity + $backorderQuantity <= 0) {
            $newQuantity = 0;
            $remove = true;
        } elseif ($cartItem->getQuantity() <= $localQuantity && $localQuantity > 0) {
            //produkt dostępny w magazynie lokalnym w dostatecznej ilości
            $productDataRow->createDefaultNotification($this->translator->trans('Cart.Module.CartItems.AlertMessage.AvailableFromLocalStock', [], 'Cart'));
            $newAvailability = CartItemInterface::ITEM_AVAILABLE_FROM_LOCAL_STOCK;
            $forceUpdateAvailability = true;
        } elseif ($cartItem->getQuantity() > $localQuantity && ($cartItem->getQuantity() - $localQuantity) <= $remoteQuantity && (!$cart->getDeliveryVariant() || $cart->getDeliveryVariant() != CartInterface::DELIVERY_VARIANT_ONLY_AVAILABLE)) {
            //produkt jest dostępny w zewnętrznym w odpowiedniej ilości
            $productDataRow->createDefaultNotification($this->translator->trans('Cart.Module.CartItems.AlertMessage.AvailableInRemoteStock', [], 'Cart'));
            $newAvailability = CartItemInterface::ITEM_AVAILABLE_FROM_REMOTE_STOCK;
        } elseif ($backorderQuantity) {
            //niezależnie od wybranego sposobu podziału, produkt nie jest dostępny w odpowiedniej ilości, ale można go zamówić
            $productDataRow->createDefaultNotification($this->translator->trans('Cart.Module.CartItems.AlertMessage.AvailableForBackorder', [], 'Cart'));
            $newAvailability = CartItemInterface::ITEM_AVAILABLE_FOR_BACKORDER;
        } elseif ($cartItem->getQuantity() > $localQuantity && ($cartItem->getQuantity() - $localQuantity) <= $remoteQuantity && $cart->getDeliveryVariant() && $cart->getDeliveryVariant() == CartInterface::DELIVERY_VARIANT_ONLY_AVAILABLE) {
            //produkt jest dostępny w zewnętrznym w odpowiedniej ilości, klient wybrał sposób podziału na paczki, tylko z lokalnego magazynu

            $productDataRow->createDefaultNotification($this->translator->trans(
                'Cart.Module.CartItems.AlertMessage.AvailableInRemoteStockButLocalSelected',
                [
                    '%originalQuantity%' => $cartItem->getQuantity(),
                    '%availableQuantity%' => $localQuantity,
                ],
                'Cart'
            ));

            $newQuantity = (float)$localQuantity;

            //wylaczamy wymuszanie z lokalnego magazynu
            $newAvailability = CartItemInterface::ITEM_AVAILABLE_FORCED_FROM_LOCAL_STOCK;
        } elseif ($cartItem->getQuantity() > $localQuantity && ($cartItem->getQuantity() - $localQuantity) > $remoteQuantity && (($cartItem->getQuantity() - $localQuantity - $remoteQuantity) <= $futureQuantity && $futureQuantity > 0) && (!$cart->getDeliveryVariant() || $cart->getDeliveryVariant() != CartInterface::DELIVERY_VARIANT_ONLY_AVAILABLE)) {
            $productDataRow->createDefaultNotification($this->translator->trans('Cart.Module.CartItems.AlertMessage.AvailableInNextShipping', [], 'Cart'));
            $newAvailability = CartItemInterface::ITEM_AVAILABLE_IN_THE_NEXT_SHIPPING;
            //nie modyfikujemy ilość zamówionych sztuk, zostawiamy to do decyzji zamawiającego
        } elseif ($cartItem->getQuantity() > $localQuantity && ($cartItem->getQuantity() - $localQuantity) > $remoteQuantity && (($cartItem->getQuantity() - $localQuantity - $remoteQuantity) > $futureQuantity && $futureQuantity > 0) && (!$cart->getDeliveryVariant() || $cart->getDeliveryVariant() != CartInterface::DELIVERY_VARIANT_ONLY_AVAILABLE)) {

            $productDataRow->createDefaultNotification($this->translator->trans(
                'Cart.Module.CartItems.AlertMessage.AvailableInNextShippingButNotEnough',
                [
                    '%originalQuantity%' => $cartItem->getQuantity(),
                    '%availableQuantity%' => $newQuantity,
                ],
                'Cart'
            ));

            //produkt będzie dostępny w kolejnych dostawach, ale w mniejszej ilości, ograniczamy automatycznie ilość sztuk
            $newQuantity = $localQuantity + $remoteQuantity + $futureQuantity;
            $newAvailability = CartItemInterface::ITEM_AVAILABLE_IN_THE_NEXT_SHIPPING;
        } elseif ($cartItem->getQuantity() > $localQuantity && ($cartItem->getQuantity() - $localQuantity) > $remoteQuantity && (($cartItem->getQuantity() - $localQuantity - $remoteQuantity) > $futureQuantity && $futureQuantity > 0) && $cart->getDeliveryVariant() && $cart->getDeliveryVariant() == CartInterface::DELIVERY_VARIANT_ONLY_AVAILABLE) {
            //produkt będzie dostępny w kolejnych dostawach, ale w mniejszej ilości, ograniczamy automatycznie ilość sztuk
            $newQuantity = $localQuantity;

            $productDataRow->createDefaultNotification($this->translator->trans(
                'Cart.Module.CartItems.AlertMessage.AvailableInNextShippingButOnlyLocalSelected',
                [
                    '%originalQuantity%' => $cartItem->getQuantity(),
                    '%availableQuantity%' => $newQuantity,
                ],
                'Cart'
            ));

            //wylaczamy wymuszanie z lokalnego magazynu
            $newAvailability = CartItem::ITEM_AVAILABLE_FORCED_FROM_LOCAL_STOCK;
            //$newAvailability = CartItem::ITEM_AVAILABLE_FROM_LOCAL_STOCK;
        } elseif ($cartItem->getQuantity() > $localQuantity && ($cartItem->getQuantity() - $localQuantity) > $remoteQuantity && (($cartItem->getQuantity() - $localQuantity - $remoteQuantity) <= $futureQuantity && $futureQuantity > 0) && $cart->getDeliveryVariant() && $cart->getDeliveryVariant() == CartInterface::DELIVERY_VARIANT_ONLY_AVAILABLE) {

            $productDataRow->createDefaultNotification($this->translator->trans(
                'Cart.Module.CartItems.AlertMessage.AvailableInNextShippingButOnlyLocalSelected',
                [
                    '%originalQuantity%' => $cartItem->getQuantity(),
                    '%availableQuantity%' => $localQuantity,
                ],
                'Cart'
            ));

            //wylaczamy wymuszanie z lokalnego magazynu
            //$newAvailability = CartItem::ITEM_AVAILABLE_FORCED_FROM_LOCAL_STOCK;
            $newAvailability = CartItem::ITEM_AVAILABLE_FROM_LOCAL_STOCK;
            //modyfikujemy ilość zamówionych sztuk, ponieważ użytkownik podjął już decyzję
            $newQuantity = (float)$localQuantity;
        } elseif (($cartItem->getQuantity() > $localQuantity) && ($cartItem->getQuantity() - $localQuantity) > $remoteQuantity && $remoteQuantity > 0 && $futureQuantity == 0 && (!$cart->getDeliveryVariant() || $cart->getDeliveryVariant() != CartInterface::DELIVERY_VARIANT_ONLY_AVAILABLE)
        ) {
            //produkt jest dostępny w zewnętrznym, ale zbyt małej ilości, zewnętrzne dostawy nie są przewidziane
            $newQuantity = (float)($localQuantity + $remoteQuantity);

            $productDataRow->createDefaultNotification(
                $this->translator->trans(
                    'Cart.Module.CartItems.AlertMessage.AvailableInRemoteStockButNotEnough',
                    [
                        '%originalQuantity%' => $cartItem->getQuantity(),
                        '%availableQuantity%' => $newQuantity,
                    ],
                    'Cart'
                ));

            $newAvailability = CartItem::ITEM_AVAILABLE_FROM_REMOTE_STOCK;
        } elseif (($cartItem->getQuantity() > $localQuantity) && $remoteQuantity == 0 && $futureQuantity == 0 && (!$cart->getDeliveryVariant() || $cart->getDeliveryVariant() != CartInterface::DELIVERY_VARIANT_ONLY_AVAILABLE)
        ) {
            $newQuantity = (float)($localQuantity);

            $productDataRow->createDefaultNotification(
                $this->translator->trans(
                    'Cart.Module.CartItems.AlertMessage.NotAvailableInRemoteStock',
                    [
                        '%originalQuantity%' => $cartItem->getQuantity(),
                        '%availableQuantity%' => $newQuantity,
                    ],
                    'Cart'
                ));

            $newAvailability = CartItem::ITEM_AVAILABLE_FROM_LOCAL_STOCK;
        } elseif (($cartItem->getQuantity() > $localQuantity) && ($cartItem->getQuantity() - $localQuantity) > $remoteQuantity && $futureQuantity == 0 && $cart->getDeliveryVariant() && $cart->getDeliveryVariant() == CartInterface::DELIVERY_VARIANT_ONLY_AVAILABLE
        ) {
            //produkt jest dostępny w zewnętrznym, ale zbyt małej ilości, zewnętrzne dostawy nie są przewidziane
            $productDataRow->createDefaultNotification($this->translator->trans(
                'Cart.tem.Label.AvailableInRemoteStockButNotEnoughButOnlyLocalSelected',
                [
                    '%originalQuantity%' => $cartItem->getQuantity(),
                    '%availableQuantity%' => $localQuantity,
                ],
                'Cart'
            ));

            $newQuantity = (float)$localQuantity;
            //wylaczamy wymuszanie z lokalnego magazynu
            //$newAvailability = CartItem::ITEM_AVAILABLE_FORCED_FROM_LOCAL_STOCK;
            $newAvailability = CartItem::ITEM_AVAILABLE_FROM_LOCAL_STOCK;
        } elseif (($cartItem->getQuantity() > $localQuantity) && $remoteQuantity == 0 && $futureQuantity == 0) {

            $productDataRow->createDefaultNotification(
                $this->translator->trans(
                    'Cart.Module.CartItems.AlertMessage.AvailableOnlyStock',
                    [
                        '%originalQuantity%' => $cartItem->getQuantity(),
                        '%availableQuantity%' => $localQuantity,
                    ],
                    'Cart'
                )
            );

            //produkt dostępny tylko w magazynie lokalnym, niedostępny w zewnętrznym i niedostępny w przyszlych dostawach
            //zmniejszamy liczbę sztuk

            $newQuantity = (float)$localQuantity;
            $newAvailability = CartItemInterface::ITEM_AVAILABLE_ONLY_FROM_LOCAL_STOCK;
        } else {
            //wszystkie stany == 0, backorder nieaktywne, usuwamy pozycję
            $remove = true;
        }

        $localAvailability = null;
        $remoteAvailability = null;
        $backorderAvailability = null;

        //Nowy sposób na określenie domyślnego sposobu podziału
        if ($localQuantity) {
            $localAvailability = CartItemInterface::ITEM_AVAILABLE_FROM_LOCAL_STOCK;
        }

        if ($remoteQuantity && $remoteStoragesCountBeforeMerge == 1) {
            $remoteAvailability = CartItemInterface::ITEM_AVAILABLE_FROM_REMOTE_STOCK;
        } elseif ($remoteQuantity && $remoteStoragesCountBeforeMerge > 1) {
            $remoteAvailability = CartItemInterface::ITEM_AVAILABLE_FROM_MULTIPLE_REMOTE_STOCKS;
        } else {
            $remoteAvailability = null;
        }

        if ($backorderQuantity) {
            $backorderAvailability = CartItemInterface::ITEM_AVAILABLE_FOR_BACKORDER;
        }

        $cartItem
            ->setLocalAvailabilityStatus($localAvailability)
            ->setRemoteAvailabilityStatus($remoteAvailability)
            ->setBackorderAvailabilityStatus($backorderAvailability);


        //TODO refaktor!
        if ($remove || ($newQuantity !== null && $newQuantity == 0)) {
            //Dostępność i konfiguracja koszyka, powodują wymuszenie stanu zerowej ilości, usuwamy pozycję
            $this->cartItemManager->remove($cartItem);
            $this->cartItemManager->flush();
        } else {
            if ((!$doNotCheckAvailability || $forceUpdateAvailability) && $newAvailability !== null) {
                $cartItem->setAvailability($newAvailability);
            }

            if ($newQuantity !== null) {
                $cartItem->setQuantity($newQuantity);
            }
        }

        return $cartItem;
    }

    /**
     * @param CartItemInterface $cartItem
     * @return Price|null
     * @throws \Exception
     */
    public function getPriceForCartItem(CartItemInterface $cartItem): ?Price
    {
        if (!$cartItem->getCart()) {
            return null;
        }

        return $this->pricelistManager->getPriceForProduct(
            $cartItem->getProduct(),
            null,
            null,
            $cartItem->getCart()->getCurrency(),
            $cartItem->getCart()->getBillingContractor(),
            $cartItem->getQuantity(true)
        );
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
            $cart->getBillingContractor(),
            $quantity
        );
    }
}