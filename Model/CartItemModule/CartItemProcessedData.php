<?php

namespace LSB\OrderBundle\Model\CartItemModule;

use LSB\OrderBundle\Model\CartItemRequestProductDataCollection;

class CartItemProcessedData
{
    /**
     * @var UpdateCounter
     */
    protected UpdateCounter $updateCounter;

    /**
     * @var array
     */
    protected array $data = [];

    /**
     * @var CartItemRequestProductDataCollection
     */
    protected CartItemRequestProductDataCollection $updateData;

    /**
     * @param UpdateCounter|null $cartItemUpdateCount
     * @param array $data
     * @param CartItemRequestProductDataCollection|null $updateData
     */
    public function __construct(?UpdateCounter $cartItemUpdateCount = null, array $data = [], ?CartItemRequestProductDataCollection $updateData = null)
    {
        if (!$cartItemUpdateCount) {
            $this->updateCounter = new UpdateCounter();
        } else {
            $this->updateCounter = $cartItemUpdateCount;
        }

        $this->data = $data;

        if ($updateData) {
            $this->updateData = $updateData;
        } else {
            $this->updateData = new CartItemRequestProductDataCollection();
        }
    }

    /**
     * @return UpdateCounter
     */
    public function getUpdateCounter(): UpdateCounter
    {
        return $this->updateCounter;
    }

    /**
     * @param UpdateCounter $updateCounter
     * @return CartItemProcessedData
     */
    public function setUpdateCounter(UpdateCounter $updateCounter): CartItemProcessedData
    {
        $this->updateCounter = $updateCounter;
        return $this;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param ${ENTRY_HINT} $data
     *
     * @return CartItemProcessedData
     */
    public function addData($data): CartItemProcessedData
    {
        if (false === in_array($data, $this->data, true)) {
            $this->data[] = $data;
        }
        return $this;
    }

    /**
     * @param ${ENTRY_HINT} $data
     *
     * @return CartItemProcessedData
     */
    public function removeData($data): CartItemProcessedData
    {
        if (true === in_array($data, $this->data, true)) {
            $index = array_search($data, $this->data);
            array_splice($this->data, $index, 1);
        }
        return $this;
    }

    /**
     * @param array $data
     * @return CartItemProcessedData
     */
    public function setData(array $data): CartItemProcessedData
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @return CartItemRequestProductDataCollection
     */
    public function getUpdateData(): CartItemRequestProductDataCollection
    {
        return $this->updateData;
    }

    /**
     * @param CartItemRequestProductDataCollection $updateData
     * @return CartItemProcessedData
     */
    public function setUpdateData(CartItemRequestProductDataCollection $updateData): CartItemProcessedData
    {
        $this->updateData = $updateData;
        return $this;
    }
}