<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Model\CartItemModule;

use Countable;
use LSB\UtilityBundle\Attribute\Serialize;

#[Serialize]
class CartItemRequestProductDataCollection implements Countable
{
    const ORDER_CODE_DEFAULT = 'default';

    protected array $collection = [];

    /**
     * @return array
     */
    public function getCollection(): array
    {
        return $this->collection;
    }

    /**
     * @return array
     */
    public function getFlatCollection(): array
    {
        $collection = [];

        /**
         * @var array $orderCodes
         */
        foreach ($this->collection as $orderCodes) {
            /**
             * @var CartItemRequestProductData $item
             */
            foreach ($orderCodes as $item) {
                $collection[] = $item;
            }
        }

        return $collection;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->collection);
    }

    /**
     * @param CartItemRequestProductData $cartItemRequestProductData
     */
    public function add(CartItemRequestProductData $cartItemRequestProductData): void
    {
        $orderCodeKey = $cartItemRequestProductData->getOrderCode() ?: self::ORDER_CODE_DEFAULT;
        $this->collection[$cartItemRequestProductData->getProductUuid()][$orderCodeKey] = $cartItemRequestProductData;
    }

    /**
     * @param CartItemRequestProductData $cartItemRequestProductData
     */
    public function remove(CartItemRequestProductData $cartItemRequestProductData): void
    {
        $orderCodeKey = $cartItemRequestProductData->getOrderCode() ?: self::ORDER_CODE_DEFAULT;

        if (isset($this->collection[$cartItemRequestProductData->getProductUuid()][$orderCodeKey])) {
            unset($this->collection[$cartItemRequestProductData->getProductUuid()][self::ORDER_CODE_DEFAULT]);
        }
    }

    /**
     * @param string $productUuid
     * @param string $orderCode
     * @return CartItemRequestProductData|null
     */
    public function get(string $productUuid, string $orderCode = self::ORDER_CODE_DEFAULT): ?CartItemRequestProductData
    {
        if (isset($this->collection[$productUuid][$orderCode])) {
            return $this->collection[$productUuid][$orderCode];
        }

        return null;
    }
}