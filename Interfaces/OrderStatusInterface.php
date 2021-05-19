<?php
/**
 * Created by PhpStorm.
 * User: krzychu
 * Date: 11.01.18
 * Time: 16:23
 */

namespace LSB\OrderBundle\Interfaces;

/**
 * Interface OrderStatusInterface
 * @package LSB\OrderBundle\Interfaces
 */
interface OrderStatusInterface extends StatusInterface
{
    //Statusy płatności
    const PAYMENT_STATUS_OPEN = 10;
    const PAYMENT_STATUS_ON_DELIVERY = 50;
    const PAYMENT_STATUS_UNPAID = 100;
    const PAYMENT_STATUS_PAID = 150;
    const PAYMENT_STATUS_FAILED = 200;
    const PAYMENT_STATUS_CANCELLED = 250;
    const PAYMENT_STATUS_FORWARDED_TO_BRANCH = 300;
    const PAYMENT_STATUS_COMPLAINT = 350;

    //Etapy, aktualnie nieużywane
    const STAGE_OPEN = 10;
    const STAGE_BOOKED = 50;
    const STAGE_CLOSED = 100;


//    /**
//     * @return mixed
//     */
//    public function getPaymentStatus();
//
//
//    /**
//     * @param $state
//     * @return mixed
//     */
//    public function setState($state);
//
//    /**
//     * @return mixed
//     */
//    public function getState();
}
