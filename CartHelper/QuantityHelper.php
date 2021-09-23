<?php
declare(strict_types=1);

namespace LSB\OrderBundle\CartHelper;

use LSB\OrderBundle\Exception\WrongPackageQuantityException;
use LSB\ProductBundle\Entity\Product;
use LSB\ProductBundle\Entity\StorageInterface;
use LSB\ProductBundle\Entity\Supplier;
use LSB\ProductBundle\Service\StorageService;
use LSB\UtilityBundle\Helper\ValueHelper;
use LSB\UtilityBundle\Value\Value;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class QuantityHelper
{
    public function __construct(
        protected StorageService $storageService,
        protected ParameterBagInterface $ps
    ){}

    /**
     * TODO switch to Value
     *
     * @param Product|null $product
     * @return int
     */
    protected function getRawLocalQuantityForProduct(?Product $product): int
    {
        return (int)($product ? $product->getLocalQuantityAvailableAtHand() : 0);
    }

    /**
     * TODO switch to Value
     *
     * @param Product|null $product
     * @param int|null $userQuantity
     * @return int
     */
    protected function getRawRemoteQuantityForProduct(?Product $product, ?int $userQuantity = null): int
    {
        //Uwaga, aktualnie nie ma możliwości ustalenia zdalnego stanu magazynowego dostawcy dlatego pozwalamy na zamówienie każdej ilości w przypadku dostawcy innego niż domyślny
        if ($userQuantity !== null && $userQuantity > 0 && $product->getUseSupplier() && $product->getSupplier() instanceof Supplier) {
            return $userQuantity;
        }

        return (int)($product ? $product->getExternalQuantityAvailableAtHand() : 0);
    }

    /**
     * The basic method for calculating the available stock stocks, calculating the available quantity for an order, keeping the separation between local and remote availability.
     * For use in rebuilding local parcels, backorder, calculating the available total
     *
     * @param Product $product
     * @param Value $userQuantity
     * @return array
     * @throws \Exception
     */
    public function calculateQuantityForProduct(Product $product, Value $userQuantity): array
    {
        $localQuantity = $this->getRawLocalQuantityForProduct($product);

        $localQuantity = $this->storageService->checkReservedQuantity(
            $product->getId(),
            (int)$userQuantity->getAmount(),
            StorageInterface::TYPE_LOCAL,
            $localQuantity
        );

        $localQuantity = ValueHelper::convertToValue($localQuantity);
        $requestedRemoteQuantity = ($userQuantity->subtract($localQuantity))->greaterThan(ValueHelper::createValueZero()) ? $userQuantity->subtract($localQuantity) : ValueHelper::createValueZero();

        //Regardless of the ordercode setting, we do not allow stocks to be booked at this stage
        [$remoteQuantity, $remoteStoragesWithShippingDays, $backOrderPackageQuantity, $remoteStoragesCountBeforeMerge] = $this->storageService->calculateRemoteShippingQuantityAndDays(
            $product,
            $requestedRemoteQuantity,
            false,
            true,
            false
        );

        $localShippingDays = $product->getShippingDays($this->ps->get('localstorage_number'));
        $remoteShippingDaysList = array_keys($remoteStoragesWithShippingDays);
        $remoteShippingDays = end($remoteShippingDaysList);

        $maxShippingDaysForUserQuantity = ($remoteShippingDays > $localShippingDays) ? $remoteShippingDays : $localShippingDays;
        $localPackageQuantity = $remotePackageQuantity = $futureQuantity = ValueHelper::createValueZero();

        if ($userQuantity <= $localQuantity) {
            $localPackageQuantity = $userQuantity;
        } elseif ($userQuantity <= ($localQuantity + $remoteQuantity + $futureQuantity + $backOrderPackageQuantity)) {
            $localPackageQuantity = $localQuantity;
            $remotePackageQuantity = $remoteQuantity;
        } else {
            //The number of items exceeds stock - it is unacceptable at this point
            throw new \Exception('Incorrect number of items in packages');
        }

        //TODO replace with value object
        return [
            $localPackageQuantity, //quantity available for local parcel
            $remotePackageQuantity, //quantity available for remote package
            $backOrderPackageQuantity, //quantity available for a package on request
            (int)$localShippingDays, //local delivery time
            (int)$remoteShippingDays, //remote delivery time
            $remoteStoragesWithShippingDays, //external warehouses with delivery time
            (int)$maxShippingDaysForUserQuantity, //maximum delivery time
            $localQuantity, //local quantity
            $remoteQuantity, //remote quantity
            $remoteStoragesCountBeforeMerge, //stany zdalne przed scaleniem
        ];
    }

    /**
     * A method designed to handle inventory levels based on a working trigger for converting available values.
     *
     * @param Product $product
     * @param int $userQuantity
     * @param bool $isBackOrderEnabled
     * @return array
     * @throws WrongPackageQuantityException
     */
    public function getCalculatedQuantityForProduct(Product $product, int $userQuantity, bool $isBackOrderEnabled = true): array
    {
        $localRawQuantity = $this->getRawLocalQuantityForProduct($product);
        $remoteRawQuantity = $this->getRawRemoteQuantityForProduct($product, $userQuantity);

        //Rezerwacja stanu lokalnego
        $localQuantity = $this->storageService->checkReservedQuantity(
            $product->getId(),
            $userQuantity,
            StorageInterface::TYPE_LOCAL,
            $localRawQuantity
        );

        $requestedRemoteQuantity = ($userQuantity - $localQuantity > 0) ? $userQuantity - $localQuantity : 0;

        $remoteQuantity = $this->storageService->checkReservedQuantity(
            $product->getId(),
            $requestedRemoteQuantity,
            StorageInterface::TYPE_EXTERNAL,
            $remoteRawQuantity
        );

        //Uwaga, aktualnie nie ma możliwości ustalenia zdalnego stanu magazynowego dostawcy dlatego pozwalamy na zamówienie każdej ilości w przypadku dostawcy zewnętrznego
        if ($requestedRemoteQuantity > 0
            && $product->isUseSupplier()
            && $product->getSupplier() instanceof Supplier
        ) {
            $remoteQuantity = $requestedRemoteQuantity;
        }

        $backOrderPackageQuantity = ($userQuantity > $localQuantity + $remoteQuantity) && $isBackOrderEnabled ? $userQuantity - $localQuantity - $remoteQuantity : 0;

        $localShippingDays = $product->getShippingDays($this->ps->get('localstorage_number'));
        $remoteShippingDaysList = [StorageInterface::DEFAULT_DELIVERY_TERM];
        $remoteShippingDays = StorageInterface::DEFAULT_DELIVERY_TERM;

        $maxShippingDaysForUserQuantity = ($remoteShippingDays > $localShippingDays) ? $remoteShippingDays : $localShippingDays;

        $futureQuantity = $localPackageQuantity = $remotePackageQuantity = 0;

        if ($userQuantity <= $localQuantity) {
            $localPackageQuantity = $userQuantity;
        } elseif ($userQuantity <= ($localQuantity + $remoteQuantity + $futureQuantity + $backOrderPackageQuantity)) {
            $localPackageQuantity = $localQuantity;
            $remotePackageQuantity = $remoteQuantity;
        } else {
            //liczba sztuk przewyższa zapasy magazynowe - w tym miejscu jest to niedopuszczalne
            throw new WrongPackageQuantityException('Wrong quantity in packages');
        }

        return [
            (int)$localPackageQuantity,
            (int)$remotePackageQuantity,
            (int)$backOrderPackageQuantity,
            (int)$localShippingDays,
            (int)$remoteShippingDays,
            $remoteStoragesWithShippingDays = [],
            (int)$maxShippingDaysForUserQuantity,
            (int)$localQuantity,
            (int)$remoteQuantity,
            $remoteStoragesCountBeforeMerge = []
        ];
    }

    /**
     * The method calculates the states taking into account local states as part of remote accessibility (then the local state can be merged with the remote state)
     *
     * @param Product $product
     * @param Value $userQuantity
     * @return array
     * @throws \Exception
     */
    public function calculateQuantityForProductWithLocalMerge(Product $product, Value $userQuantity): array
    {
        [
            $calculatedQuantity,
            $storagesWithShippingDays,
            $backorderPackageQuantity,
            $storagesCountBeforeMerge
        ] = $this->storageService->calculateRemoteShippingQuantityAndDays(
            $product,
            $userQuantity,
            $useLocalStorageAsRemote = true,
            $this->ps->get('cart.ordercode.enabled') ? true : false,
            false
        );

        $shippingDaysList = array_keys($storagesWithShippingDays);
        $shippingDays = end($shippingDaysList);

        //TODO refactor
        return [
            (int)$calculatedQuantity,
            (int)$backorderPackageQuantity,
            (int)$shippingDays,
            $storagesWithShippingDays,
            $storagesCountBeforeMerge,
        ];
    }
}