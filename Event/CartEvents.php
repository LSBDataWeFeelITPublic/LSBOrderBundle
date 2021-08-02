<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Event;

/**
 * Class CartEvents
 * @package LSB\OrderBundle\Event
 */
class CartEvents
{
    const CART_MERGED = 'cart.merged';

    const CART_SESSION_CONVERTED_TO_USER = 'cart.session_converted_to_user_cart';

    const CART_CLEARED = 'cart.cleared';

    const CART_CREATED = 'cart.created';

    const CART_CLOSED = 'cart.closed';

    const CART_SUMMARY_CALCULATED = 'cart.summary.calculated';

    const CART_ITEM_CREATED = 'cart.item.created';

    const CART_ITEM_UPDATED = 'cart.item.updated';

    const CART_ITEM_REMOVED = 'cart.item.removed';

    const CART_PAYMENT_METHOD_CHANGED = 'cart.payment_method.changed';

    const CART_AUTH_USER_LOGGED = 'cart.auth.user_logged';

    const CART_AUTH_REGISTRATION_COMPLETED = 'cart.auth.registration_completed';

    const CART_AUTH_WITHOUT_REGISTRATION = 'cart.auth.without_registration';

    const CART_CUSTOMER_TYPE_CHANGED = 'cart.customer_type.changed';

    const CART_BILLING_INFORMATION_UPDATED = 'cart.billing_information.updated';

    const CART_BILLING_INFORMATION_ACCOUNT_CREATED = 'cart.billing_information.account_created';

    const CART_DELIVERY_DATA_UPDATED = 'cart.delivery_data.updated';

    const CART_ABANDONED_FETCHED = 'cart.abandoned.fetched';

    const CART_ABANDONED_PROCESSED = 'cart.abandoned.processed';
}