<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Interfaces;

/**
 * Interface OrderStatusInterface
 * @package LSB\OrderBundle\Interfaces
 */
interface StatusInterface
{
    //Statusy realizacji zamówienia
    const STATUS_OPEN = 10;                         //utworzone
    const STATUS_CONFIGURED = 20           ;        //skonfigurowane, przetwarzane
    const STATUS_WAITING_FOR_CONFIRMATION = 30;     //ale wymaga akceptacji mailowej przez klienta
    const STATUS_CONFIRMED = 40;                    //potwierdzone mailowo lub potwierdzone automatycznie (gdy użytkownik jest zalogowany)
    const STATUS_WAITING_FOR_VERIFICATION = 50;     //oczekuje na weryfikację przez obsługę sklepu
    const STATUS_VERIFIED = 60;                     //zweryfikowane przez handlowca
    const STATUS_WAITING_FOR_PAYMENT = 70;          //oczekiwanie na płatność
    const STATUS_PAID = 80;                         //opłacone (gdy oczekiwano płatności elektronicznej)
    const STATUS_PLACED = 90;                       //złożone, przyjęte przez system - rp
    const STATUS_PROCESSING = 100;                  //w trakcie realizacji
    const STATUS_SHIPPING_PREPARE = 110;             //w trakcie przygotowywania paczki
    const STATUS_SHIPPING_PREPARED = 120;            //gotowe do wysyłki
    const STATUS_SHIPPED = 130;                      //wysłane
    const STATUS_COMPLETED = 140;                    //zrealizowane w całości
    const STATUS_FORWARDED = 150;          //przekazane do realizacji do oddziału
    const STATUS_CANCELED = 210;
    const STATUS_REJECTED = 220;                     //odrzucone

//    /**
//     * @return mixed
//     */
//    public function getStatus();
//
//    /**
//     * @param $status
//     * @return mixed
//     */
//    public function setStatus($status);
}
