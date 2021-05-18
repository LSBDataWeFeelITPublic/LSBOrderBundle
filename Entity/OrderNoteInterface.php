<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Entity;

/**
 * Interface OrderNoteInterface
 * @package LSB\OrderBundle\Entity
 */
interface OrderNoteInterface
{
    const TYPE_SELLER_NOTE = 1;
    const TYPE_SELLER_VERIFICATION_NOTE = 5;
    const TYPE_USER_NOTE = 10;
    const TYPE_USER_VERIFICATION_REQUEST_NOTE = 11;
    const TYPE_USER_DELIVERY_NOTE = 12;
    const TYPE_USER_INVOICE_NOTE = 13;
    const TYPE_USER_NAME = 15;
    const TYPE_MODERATOR_REJECT_NOTE = 20;
    const TYPE_MODERATOR_NOTE = 20;
    const TYPE_AUTO_GENERATED_NOTE = 100;
    const TYPE_AUTO_PRODUCT_SET_NOTE = 110;
    const TYPE_INTERNAL_NOTE = 200;
}