<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Entity;

use LSB\UtilityBundle\Interfaces\UuidInterface;

/**
 * Interface CartInterface
 * @package LSB\OrderBundle\Entity
 */
interface CartInterface extends UuidInterface
{
    /**
     * Delivery variant
     */
    const DELIVERY_VARIANT_ASK_QUESTION = 0;
    const DELIVERY_VARIANT_ONLY_AVAILABLE = 10;
    const DELIVERY_VARIANT_SEND_AVAILABLE = 20;
    const DELIVERY_VARIANT_WAIT_FOR_ALL = 30;
    const DELIVERY_VARIANT_WAIT_FOR_BACKORDER = 40;

    /**
     * Processing type
     */
    const PROCESSING_TYPE_DEFAULT = OrderInterface::PROCESSING_TYPE_DEFAULT; //Domyślny sposób obsługi zamówienia - poprzez

    /**
     * Steps
     */
    const CART_STEP_1 = 1;
    const CART_STEP_2 = 2;
    const CART_STEP_3 = 3;
    const CART_STEP_4 = 4;
    const CART_STEP = 10; //TODO replace with CART_STEP_ORDER_CREATED
    const CART_STEP_ORDER_CREATED = 50; // Cart validation end step
    const CART_STEP_CLOSED_MANUALLY = 100;
    const CART_STEP_CLOSED_BY_MERGE = 101;


    /**
     * Invoice delivery type
     */
    const INVOICE_DELIVERY_TYPE_NOT_SET = 0;
    const INVOICE_DELIVERY_USE_CUSTOMER_DATA = 998;
    const INVOICE_DELIVERY_USE_NEW_ADDRESS = 999;

    /**
     * Type
     */
    const CART_TYPE_SESSION = 1;
    const CART_TYPE_USER = 2;

    /**
     * Auth type
     */
    const AUTH_TYPE_USER = 10;
    const AUTH_TYPE_REGISTRATION = 20;
    const AUTH_TYPE_WITHOUT_REGISTRATION = 30;
}