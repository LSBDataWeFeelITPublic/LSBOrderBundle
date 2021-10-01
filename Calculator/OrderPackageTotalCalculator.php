<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Calculator;

use LSB\LocaleBundle\Manager\TaxManager;
use LSB\OrderBundle\Entity\Order;
use LSB\OrderBundle\Entity\OrderInterface;
use LSB\OrderBundle\Entity\OrderPackage;
use LSB\OrderBundle\Entity\OrderPackageInterface;
use LSB\OrderBundle\Entity\OrderPackageItem;
use LSB\OrderBundle\Entity\PackageItem;
use LSB\OrderBundle\Entity\PackageItemInterface;
use LSB\PricelistBundle\Calculator\BaseTotalCalculator;
use LSB\PricelistBundle\Calculator\Result;
use LSB\UtilityBundle\Helper\ValueHelper;
use LSB\UtilityBundle\Value\Value;

/**
 * Class OrderPackageTotalCalculator
 * @package LSB\OrderBundle\Calculator
 */
class OrderPackageTotalCalculator extends BaseTotalCalculator
{
    protected const SUPPORTED_CLASS = OrderPackage::class;

    protected const SUPPORTED_POSITION_CLASS = OrderPackageItem::class;

    /**
     * @param $subject
     * @param array $options
     * @param string|null $applicationCode
     * @param bool $updateSubject
     * @param bool $updatePositions
     * @param array $calculationRes
     * @return Result
     * @throws \Exception
     */
    public function calculateTotal(
        $subject,
        array $options = [],
        ?string $applicationCode = null,
        bool $updateSubject = true,
        bool $updatePositions = true,
        array &$calculationRes = []
    ): Result {
        if (!$subject instanceof OrderPackage) {
            throw new \Exception('Subject must be OrderPackage');
        }

        if (!$subject->getOrder() instanceof Order) {
            throw new \Exception('Order is required');
        }

        $nettoCalculation = $subject->getOrder()->getCalculationType() === OrderInterface::CALCULATION_TYPE_GROSS ? false : true;



        $calculationProductsRes = [];
        $calculationShippingRes = [];
        $calculationPaymentCostRes = [];

        $positionCalculationResult = $this->calculatePositions($subject, $options, $applicationCode, $updatePositions);

        if ($positionCalculationResult->isSuccess()) {
            $calculationProductsRes = TaxManager::mergeMoneyRes($positionCalculationResult->getCalculationRes(), $calculationProductsRes);
        }

        if ($nettoCalculation) {
            [$totalProductsNet, $totalProductsGross] = TaxManager::calculateMoneyTotalNettoAndGrossFromNettoRes($subject->getOrder()->getCurrencyIsoCode(), $calculationProductsRes);
        } else {
            [$totalProductsNet, $totalProductsGross] = TaxManager::calculateMoneyTotalNettoAndGrossFromGrossRes($subject->getOrder()->getCurrencyIsoCode(), $calculationProductsRes);
        }

        $calculationRes = $calculationProductsRes;

        //Doliczamy koszt wysyłki
        $this->calculateShippingCost($subject, $calculationShippingRes, $calculationRes, $nettoCalculation, $updateSubject);

        //Doliczamy koszt płatności
        $this->calculatePaymentCost($subject, $calculationPaymentCostRes, $calculationRes, $nettoCalculation, $updateSubject);

        if ($nettoCalculation) {
            [$totalNet, $totalGross] = TaxManager::calculateMoneyTotalNettoAndGrossFromNettoRes($subject->getOrder()->getCurrencyIsoCode(), $calculationRes);
        } else {
            [$totalNet, $totalGross] = TaxManager::calculateMoneyTotalNettoAndGrossFromGrossRes($subject->getOrder()->getCurrencyIsoCode(), $calculationRes);
        }

        if ($updateSubject) {
            $subject
                ->setTotalValueNet($totalNet)
                ->setTotalValueGross($totalGross)
                ->setProductsValueNet($totalProductsNet)
                ->setProductsValueGross($totalProductsGross);
        }

        return new Result(
            true,
            $subject->getOrder()->getCurrency(),
            $totalProductsNet,
            $totalProductsGross,
            $subject,
            $calculationRes,
            $calculationProductsRes,
            $calculationShippingRes,
            $calculationPaymentCostRes
        );
    }

    /**
     * @param OrderPackage $orderPackage
     * @param array $shippingCostRes
     * @param array $calculationRes
     * @param bool $nettoCalculation
     * @param bool $updateSubject
     * @throws \Exception
     */
    protected function calculateShippingCost(
        OrderPackage $orderPackage,
        array &$shippingCostRes,
        array &$calculationRes,
        bool $nettoCalculation = true,
        bool $updateSubject = true
    ): void {
        $addTax = $this->addTax($orderPackage);

        /**
         * @var OrderPackageItem $orderPackageItem
         */
        foreach ($orderPackage->getOrderPackageItems() as $orderPackageItem) {

            if ($orderPackageItem->getType() !== PackageItemInterface::TYPE_SHIPPING) {
                continue;
            }

            $taxPercentage = $this->calculateTaxPercentage($orderPackageItem, $addTax);
            $this->recalculatePackageItemValues($orderPackageItem, $nettoCalculation);


            if ($nettoCalculation) {
                TaxManager::addMoneyValueToNettoRes(
                    $taxPercentage,
                    $orderPackageItem->getValueNet(true) ?? ValueHelper::createMoneyZero($orderPackage->getOrder()->getCurrencyIsoCode()),
                    $shippingCostRes
                );

                TaxManager::addMoneyValueToNettoRes(
                    $taxPercentage,
                    $orderPackageItem->getValueNet(true) ?? ValueHelper::createMoneyZero($orderPackage->getOrder()->getCurrencyIsoCode()),
                    $calculationRes
                );
            } else {
                TaxManager::addMoneyValueToGrossRes(
                    $taxPercentage,
                    $orderPackageItem->getValueGross(true) ?? ValueHelper::createMoneyZero($orderPackage->getOrder()->getCurrencyIsoCode()),
                    $shippingCostRes
                );

                TaxManager::addMoneyValueToGrossRes(
                    $taxPercentage,
                    $orderPackageItem->getValueGross(true) ?? ValueHelper::createMoneyZero($orderPackage->getOrder()->getCurrencyIsoCode()),
                    $calculationRes
                );
            }
        }

        if ($nettoCalculation) {
            [$totalShippingNetto, $totalShippingGross] = TaxManager::calculateMoneyTotalNettoAndGrossFromNettoRes(
                $orderPackage->getOrder()->getCurrencyIsoCode(),
                $shippingCostRes
            );
        } else {
            [$totalShippingNetto, $totalShippingGross] = TaxManager::calculateMoneyTotalNettoAndGrossFromGrossRes(
                $orderPackage->getOrder()->getCurrencyIsoCode(),
                $shippingCostRes
            );
        }

        if ($updateSubject) {
            $orderPackage
                ->setShippingCostNet($totalShippingNetto)
                ->setShippingCostGross($totalShippingGross);
        }
    }

    /**
     * @param OrderPackage $orderPackage
     * @param array $paymentCostRes
     * @param array $calculationRes
     * @param bool $nettoCalculation
     * @param bool $updateSubject
     * @throws \Exception
     */
    protected function calculatePaymentCost(
        OrderPackage $orderPackage,
        array &$paymentCostRes,
        array &$calculationRes,
        bool $nettoCalculation = true,
        bool $updateSubject = true
    ): void {
        $addTax = $this->addTax($orderPackage);

        /**
         * @var OrderPackageItem $orderPackageItem
         * Do NOT use $orderPackage->getPaymentTypeOrderPackageItems(), because $orderPackage at this moment could be not flushed
         */
        foreach ($orderPackage->getOrderPackageItems() as $orderPackageItem) {

            if ($orderPackageItem->getType() !== PackageItemInterface::TYPE_PAYMENT) {
                continue;
            }

            $taxPercentage = $this->calculateTaxPercentage($orderPackageItem, $addTax);
            $this->recalculatePackageItemValues($orderPackageItem, $nettoCalculation);

            if ($nettoCalculation) {
                TaxManager::addMoneyValueToNettoRes(
                    $taxPercentage,
                    $orderPackageItem->getValueNet(true) ?? ValueHelper::createMoneyZero($orderPackage->getOrder()->getCurrencyIsoCode()),
                    $paymentCostRes
                );

                TaxManager::addMoneyValueToNettoRes(
                    $taxPercentage,
                    $orderPackageItem->getValueNet(true) ?? ValueHelper::createMoneyZero($orderPackage->getOrder()->getCurrencyIsoCode()),
                    $calculationRes
                );
            } else {
                TaxManager::addMoneyValueToGrossRes(
                    $taxPercentage,
                    $orderPackageItem->getValueGross(true) ?? ValueHelper::createMoneyZero($orderPackage->getOrder()->getCurrencyIsoCode()),
                    $paymentCostRes
                );

                TaxManager::addMoneyValueToGrossRes(
                    $taxPercentage,
                    $orderPackageItem->getValueGross(true) ?? ValueHelper::createMoneyZero($orderPackage->getOrder()->getCurrencyIsoCode()),
                    $calculationRes
                );
            }
        }



        if ($nettoCalculation) {
            [$totalPaymentNet, $totalPaymentGross] = TaxManager::calculateMoneyTotalNettoAndGrossFromNettoRes($orderPackage->getOrder()->getCurrencyIsoCode(), $paymentCostRes);
        } else {
            [$totalPaymentNet, $totalPaymentGross] = TaxManager::calculateMoneyTotalNettoAndGrossFromGrossRes($orderPackage->getOrder()->getCurrencyIsoCode(), $paymentCostRes);
        }

        if ($updateSubject) {
            $orderPackage
                ->setPaymentCostNet($totalPaymentNet)
                ->setPaymentCostGross($totalPaymentGross);
        }
    }

    /**
     * @param OrderPackage $subject
     * @param array $options
     * @param string|null $applicationCode
     * @param bool $updatePositions
     * @return Result
     * @throws \Exception
     */
    public function calculatePositions(
        $subject,
        array $options,
        ?string $applicationCode,
        bool $updatePositions = true
    ): Result {
        if (!$subject instanceof OrderPackage) {
            throw new \Exception('Wrong calculation subject');
        }

        $nettoCalculation = $this->isNettoCalculation($subject);
        $calculationRes = [];

        $addTax = $this->addTax($subject);

        if ($subject->getOrderPackageItems()->count()) {
            /**
             * @var OrderPackageItem $packageItem
             */
            foreach ($subject->getOrderPackageItems() as $packageItem) {

                if ($packageItem->getType() !== PackageItemInterface::TYPE_DEFAULT) {
                    continue;
                }

                $this->recalculatePackageItemValues($packageItem, $nettoCalculation);
                $taxPercentage = $this->calculateTaxPercentage($packageItem, $addTax);
                if ($nettoCalculation) {
                    TaxManager::addMoneyValueToNettoRes($taxPercentage, $packageItem->getPriceNet(true)->multiply($packageItem->getQuantity(true)?->getRealStringAmount() ?? 0), $calculationRes);
                } else {
                    TaxManager::addMoneyValueToGrossRes($taxPercentage, $packageItem->getPriceGross(true)->multiply($packageItem->getQuantity(true)?->getRealStringAmount() ?? 0), $calculationRes);
                }
            }
        }

        return new Result(
            true,
            $subject->getOrder()->getCurrency(),
            ValueHelper::createMoneyZero($subject->getOrder()->getCurrencyIsoCode()),
            ValueHelper::createMoneyZero($subject->getOrder()->getCurrencyIsoCode()),
            $subject,
            $calculationRes,
            $calculationRes
        );
    }

    /**
     * @param OrderPackageItem $orderPackageItem
     * @param bool $nettoCalculation
     * @param bool|null $addTax
     * @return PackageItem
     * @throws \Exception
     */
    public function recalculatePackageItemValues(OrderPackageItem $orderPackageItem, bool $nettoCalculation = true, ?bool $addTax = null): PackageItem
    {
//        if ($nettoCalculation && $orderPackageItem->getPriceNet() !== null) {
//            $orderPackageItem->setPriceNet($orderPackageItem->getPriceNet());
//        } elseif (!$nettoCalculation && $orderPackageItem->getPriceGross() !== null) {
//            $orderPackageItem->setPriceGross($orderPackageItem->getPriceGross());
//        }

        $defaultTax = ValueHelper::convertToValue(23); //Fixed for tests

        if ($addTax === null) {
            $addTax = $orderPackageItem->getOrderPackage() && $orderPackageItem->getOrderPackage()->getOrder() ? $orderPackageItem->getOrderPackage()->getOrder()->getVatCalculationType() === OrderInterface::VAT_CALCULATION_TYPE_ADD : true;
        }


        if (!$addTax) {
            $taxPercentage = ValueHelper::createValueZero();
        } elseif ($orderPackageItem->getTaxRate() !== null) {
            $taxPercentage = $orderPackageItem->getTaxRate(true);
        } elseif ($orderPackageItem->getTaxRate(true) === null && (($calculatedTax = $this->calculateTaxFromPrices($orderPackageItem)) !== null)) {
            $taxPercentage = $calculatedTax;
        } elseif ($defaultTax) {
            $taxPercentage = $defaultTax;
        } else {
            $taxPercentage = ValueHelper::createValueZero();
        }

        if ($nettoCalculation) {
            if ($orderPackageItem->getPriceNet(true) !== null && $orderPackageItem->getQuantity(true) !== null) {
                $valueNet = $orderPackageItem->getPriceNet(true)->multiply($orderPackageItem->getQuantity(true)->getRealStringAmount());
                $orderPackageItem->setValueNet($valueNet);
            }

            if ($orderPackageItem->getValueNet(true) !== null && $orderPackageItem->getQuantity(true) !== null) {
                $orderPackageItem->setValueGross(TaxManager::calculateMoneyGrossValue($orderPackageItem->getValueNet(true), $taxPercentage));
            }
        } else {
            if ($orderPackageItem->getPriceGross(true) !== null && $orderPackageItem->getQuantity(true) !== null) {
                $orderPackageItem->setValueGross($orderPackageItem->getPriceGross()->multiply($orderPackageItem->getQuantity(true)->getRealStringAmount()));
            }

            if ($orderPackageItem->getValueGross(true) !== null && $orderPackageItem->getQuantity(true) !== null) {
                $orderPackageItem->setValueNet(TaxManager::calculateMoneyNetValue($orderPackageItem->getValueGross(true), $taxPercentage));
            }
        }

        return $orderPackageItem;
    }

//    /**
//     * @param OrderPackageItem $orderPackageItem
//     * @param bool $nettoCalculation
//     * @return void
//     */
//    public function recalculateOrderPackageItemValues(OrderPackageItem $orderPackageItem, bool $nettoCalculation = true): void
//    {
//        if ($orderPackageItem->isUpdateValues()) {
//            if ($orderPackageItem->getPriceNet(true) !== null && $orderPackageItem->getQuantity(true) !== null) {
//
//
//                $orderPackageItem->setValueNet((int) round($orderPackageItem->getQuantity() * $orderPackageItem->getPriceNet(), 0));
//
//
//                if ($nettoCalculation || $orderPackageItem->getPriceGross() === null) {
//                    $orderPackageItem->setPriceGross((int) round($orderPackageItem->getPriceNet() * ((100 + (int)$orderPackageItem->getTaxRate()) / 100), 0));
//                }
//            }
//
//            if ($orderPackageItem->getPriceGross() !== null && $orderPackageItem->getQuantity() !== null) {
//                $orderPackageItem->setValueGross((int) round($orderPackageItem->getQuantity() * $orderPackageItem->getGrossPrice(), 0));
//            }
//        }
//    }

    /**
     * @param OrderPackageItem $orderPackageItem
     * @param bool $updateItem
     * @return Value|null
     * @throws \Exception
     */
    public function calculateTaxFromPrices(
        OrderPackageItem $orderPackageItem,
        bool $updateItem = false
    ): ?Value {
        if ($orderPackageItem->getPriceGross(true)
            && $orderPackageItem->getPriceNet(true)
            && $orderPackageItem->getPriceGross(true)->greaterThanOrEqual($orderPackageItem->getPriceNet(true))) {

            $precision = ValueHelper::getCurrencyPrecision($orderPackageItem->getCurrencyIsoCode());
            $percents100 = ValueHelper::get100Percents($precision);
            $tax = ValueHelper::intToValue((int) round($percents100 * $orderPackageItem->getPriceGross() / $orderPackageItem->getPriceNet() - $percents100, 0), '%');

            if ($updateItem) {
                $orderPackageItem->setTaxRate($tax);
            }

            return $tax;
        }

        return null;
    }

    /**
     * @param OrderPackageItem $orderPackageItem
     * @param bool $addTax
     * @return Value
     * @throws \Exception
     */
    protected function calculateTaxPercentage(OrderPackageItem $orderPackageItem, bool $addTax): Value
    {
        if (!$addTax) {
            $taxPercentage = 0;
        } elseif ($orderPackageItem->getTaxRate() !== null) {
            $taxPercentage = $orderPackageItem->getTaxRate(true);
        } elseif ($orderPackageItem->getTaxRate() === null && ($calculatedTax = $this->calculateTaxFromPrices($orderPackageItem))) {
            $taxPercentage = $calculatedTax;
        } else {
            $taxPercentage = ValueHelper::convertToValue(23); //TODO FIXED
        }

        return $taxPercentage;
    }

    /**
     * @param OrderPackageInterface $orderPackage
     * @return bool
     */
    protected function addTax(OrderPackageInterface $orderPackage): bool
    {
        return !$orderPackage->getOrder() || $orderPackage->getOrder()->getVatCalculationType() === OrderInterface::VAT_CALCULATION_TYPE_ADD;
    }

    /**
     * @param OrderPackageInterface $orderPackage
     * @return bool
     */
    protected function isNettoCalculation(OrderPackageInterface $orderPackage): bool
    {
        return !($orderPackage->getOrder() && $orderPackage->getOrder()->getCalculationType() === OrderInterface::CALCULATION_TYPE_GROSS);
    }
}