<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Model\CartItemModule;

class CartItemUpdateResult
{
    /**
     * @var CartItemProcessedData
     */
    protected CartItemProcessedData $processedItems;

    /**
     * @param CartItemProcessedData|null $processedItems
     */
    public function __construct(?CartItemProcessedData $processedItems = null)
    {
        if (!$processedItems) {
            $processedItems = new CartItemProcessedData();
        }

        $this->processedItems = $processedItems;
    }

    /**
     * @return CartItemProcessedData
     */
    public function getProcessedItems(): CartItemProcessedData
    {
        return $this->processedItems;
    }
}