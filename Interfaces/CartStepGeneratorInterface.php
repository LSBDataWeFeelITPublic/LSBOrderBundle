<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Interfaces;

use LSB\ContractorBundle\Entity\ContractorInterface;
use LSB\OrderBundle\CartModule\CartModuleInterface;
use LSB\OrderBundle\Entity\CartInterface;
use LSB\UserBundle\Entity\UserInterface;
use LSB\UtilityBundle\Module\ModuleInterface;
use Symfony\Component\HttpFoundation\Request;

interface CartStepGeneratorInterface extends ModuleInterface
{
    const STEP = 0;

    const CODE = 'default';

    const SESSION_CREATED_ORDER_ID_KEY = 'generator/createdOrderId';

    /**
     * Zwraca identyfikator kroku (int)
     *
     * @return int
     */
    public function getStep(): int;

    /**
     * Zwraca identyfikator kroku (string)
     *
     * @return string
     */
    public function getCode(): string;

    /**
     * Metoda walidującą poprawność wypełnienia danego kroku
     * Wszelka walidacja powinna bazować właśnie na tej metodzie!
     * Niezależnie od tego czy korzystamy z walidacji w enities
     */
    public function validate();

    /**
     * @param CartInterface|null $cart
     * @return array
     */
    public function isAccessible(?CartInterface $cart = null): array;

    /**
     * Metoda przygotowuje koszyk do wykonania danego kroku
     * Może uruchomić walidację poprzednich kroków lub sprawdzić spójność danych
     *
     * @return
     */
    public function prepare();

    /**
     * Zwraca posortowaną listę modułów do renderowania
     */
    public function getModules(): array;

    /**
     * Zwraca listę modułów dla danego kroku
     * @return array
     */
    public function getModuleList(): array;

    /**
     * Metoda wyświetlająca dany modułów
     *
     * @param Request|null $request
     * @param bool $doPrepare
     * @return array
     */
    public function renderModules(?Request $request = null, bool $doPrepare = true): array;

    /**
     * @param CartInterface $cart
     * @param CartModuleInterface $module
     * @param bool $isInitalRender
     * @param Request|null $request
     * @return mixed
     */
    public function renderModule(CartInterface $cart, CartModuleInterface $module, bool $isInitalRender, ?Request $request = null);

    /**
     * @param CartInterface|null $cart
     * @return bool
     */
    public function isViewable(?CartInterface $cart = null): bool;


    /**
     * Metoda sprawdza, czy krok nie generuje przekierowania
     * Jeżeli krok generuje przekierowanie należy bewzględnie z tego skorzystać
     *
     * @param bool $doPrepare
     * @return string
     */
    public function getRedirect(bool $doPrepare = true): ?string;

    /**
     * Metoda konfiguruje generator w kontekście użytkownika i klienta
     *
     * @param UserInterface|null $user
     * @param ContractorInterface|null $customer
     * @param CartInterface|null $cart
     * @return mixed
     */
    public function configure(?UserInterface $user = null, ?ContractorInterface $customer = null, ?CartInterface $cart = null);

    /**
     * Metoda nawigacyjna zwraca następny i poprzedni krok generatora
     *
     * @return array
     */
    public function getNavigation(): array;
}
