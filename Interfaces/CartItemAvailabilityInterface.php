<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Interfaces;

interface CartItemAvailabilityInterface
{
    const ITEM_AVAILABLE_UNKNOWN = 0;
    const ITEM_AVAILABLE_FROM_LOCAL_STOCK = 10;
    const ITEM_AVAILABLE_ONLY_FROM_LOCAL_STOCK = 11;
    const ITEM_AVAILABLE_FORCED_FROM_LOCAL_STOCK = 12;
    const ITEM_AVAILABLE_FROM_REMOTE_STOCK = 20;
    const ITEM_AVAILABLE_FROM_MULTIPLE_REMOTE_STOCKS = 21;
    const ITEM_AVAILABLE_IN_THE_NEXT_SHIPPING = 30;
    const ITEM_AVAILABLE_FOR_BACKORDER = 40;
    const ITEM_AVAILABLE_FOR_BACKORDER_AND_LOCAL_STOCK = 41; //obecnie niewykorzystywane
    const ITEM_AVAILABLE_FOR_BACKORDER_AND_REMOTE_STOCK = 42; //obecnie niewykorzystywane
    const ITEM_AVAILABLE_FOR_BACKORDER_FROM_MANY_STOCKS = 43;

    /**
     * @return null
     */
    public function getTotalAvailability();

    /**
     * @param null $totalAvailability
     */
    public function setTotalAvailability($totalAvailability);

    /**
     * @return mixed
     */
    public function getLocalAvailability();

    /**
     * @param $localAvailability
     * @return mixed
     */
    public function setLocalAvailability($localAvailability);

    /**
     * @return mixed
     */
    public function getRemoteAvailability();

    /**
     * @param $remoteAvailability
     * @return mixed
     */
    public function setRemoteAvailability($remoteAvailability);

    /**
     * @return mixed
     */
    public function getBackorderAvailability();

    /**
     * @param $backorderAvailability
     * @return mixed
     */
    public function setBackorderAvailability($backorderAvailability);

}