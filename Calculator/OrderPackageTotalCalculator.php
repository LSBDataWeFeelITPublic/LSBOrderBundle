<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Calculator;

use LSB\LocaleBundle\Manager\TaxManager;
use LSB\OrderBundle\Entity\OrderInterface;
use LSB\OrderBundle\Entity\OrderPackage;
use LSB\OrderBundle\Entity\OrderPackageInterface;
use LSB\OrderBundle\Entity\OrderPackageItem;
use LSB\OrderBundle\Entity\PackageItem;
use LSB\PricelistBundle\Calculator\BaseTotalCalculator;
use LSB\PricelistBundle\Calculator\Result;

/**
 * Class OrderPackageTotalCalculator
 * @package LSB\OrderBundle\Calculator
 */
class OrderPackageTotalCalculator extends BaseTotalCalculator
{
    protected const SUPPORTED_CLASS = OrderPackage::class;

    protected const SUPPORTED_POSITION_CLASS = OrderPackageItem::class;

    /**
     * @param OrderPackage $subject
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
        array $options,
        ?string $applicationCode,
        bool $updateSubject = true,
        bool $updatePositions = true,
        array &$calculationRes = []
    ): Result {
        if (!$subject instanceof OrderPackage) {
            throw new \Exception('Subject must be OrderPackage');
        }

        $nettoCalculation = $subject->getOrder() && $subject->getOrder()->getCalculationType() === OrderInterface::CALCULATION_TYPE_GROSS ? false : true;

        $calculationProductsRes = [];
        $calculationShippingRes = [];
        $calculationPaymentCostRes = [];

        $positionCalculationResult = $this->calculatePositions($subject, $options, $applicationCode, $updatePositions);

        if ($positionCalculationResult->isSuccess()) {
            $calculationProductsRes = TaxManager::mergeRes($positionCalculationResult->getCalculationRes(), $calculationProductsRes);
        }

        if ($nettoCalculation) {
            [$totalProductsNet, $totalProductsGross] = TaxManager::calculateTotalNettoAndGrossFromNettoRes($calculationProductsRes);
        } else {
            [$totalProductsNet, $totalProductsGross] = TaxManager::calculateTotalNettoAndGrossFromGrossRes($calculationProductsRes);
        }

        $calculationRes = $calculationProductsRes;

        //Doliczamy koszt wysyłki
        $this->calculateShippingCost($subject, $calculationShippingRes, $calculationRes, $nettoCalculation, $updateSubject);

        //Doliczamy koszt wysyłki
        $this->calculatePaymentCost($subject, $calculationPaymentCostRes, $calculationRes, $nettoCalculation, $updateSubject);

        if ($nettoCalculation) {
            [$totalNet, $totalGross] = TaxManager::calculateTotalNettoAndGrossFromNettoRes($calculationRes);
        } else {
            [$totalNet, $totalGross] = TaxManager::calculateTotalNettoAndGrossFromGrossRes($calculationRes);
        }

        if ($updateSubject) {
            $subject
                ->setTotalValueNet((int)$totalNet)
                ->setTotalValueGross((int)$totalGross)
                ->setProductsValueNet((int)$totalProductsNet)
                ->setProductsValueGross((int)$totalProductsGross);
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
        foreach ($orderPackage->getShippingTypeOrderPackageItems() as $orderPackageItem) {
            $taxPercentage = $this->calculateTaxPercentage($orderPackageItem, $addTax);
            $this->recalculatePackageItemValues($orderPackageItem, $nettoCalculation);

            if ($nettoCalculation) {
                TaxManager::addValueToNettoRes($taxPercentage, $orderPackageItem->getValueNet(), $shippingCostRes);
                TaxManager::addValueToNettoRes($taxPercentage, $orderPackageItem->getValueNet(), $calculationRes);
            } else {
                TaxManager::addValueToGrossRes($taxPercentage, $orderPackageItem->getValueGross(), $shippingCostRes);
                TaxManager::addValueToGrossRes($taxPercentage, $orderPackageItem->getValueGross(), $calculationRes);
            }
        }

        if ($nettoCalculation) {
            [$totalShippingNetto, $totalShippingGross] = TaxManager::calculateTotalNettoAndGrossFromNettoRes($shippingCostRes);
        } else {
            [$totalShippingNetto, $totalShippingGross] = TaxManager::calculateTotalNettoAndGrossFromGrossRes($shippingCostRes);
        }

        if ($updateSubject) {
            $orderPackage
                ->setShippingCostNet((int)$totalShippingNetto)
                ->setShippingCostGross((int)$totalShippingGross);
        }
    }

    /**
     * @param OrderPackage $orderPackage
     * @param array $paymentCostRes
     * @param array $calculationRes
     * @param bool $nettoCalculation
     * @param bool $updateSubject
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
         */
        foreach ($orderPackage->getPaymentTypeOrderPackageItems() as $orderPackageItem) {
            $taxPercentage = $this->calculateTaxPercentage($orderPackageItem, $addTax);
            $this->recalculatePackageItemValues($orderPackageItem, $nettoCalculation);

            if ($nettoCalculation) {
                TaxManager::addValueToNettoRes($taxPercentage, $orderPackageItem->getValueNet(), $paymentCostRes);
                TaxManager::addValueToNettoRes($taxPercentage, $orderPackageItem->getValueNet(), $calculationRes);
            } else {
                TaxManager::addValueToGrossRes($taxPercentage, $orderPackageItem->getValueGross(), $paymentCostRes);
                TaxManager::addValueToGrossRes($taxPercentage, $orderPackageItem->getValueGross(), $calculationRes);
            }
        }

        if ($nettoCalculation) {
            [$totalPaymentNetto, $totalPaymentGross] = TaxManager::calculateTotalNettoAndGrossFromNettoRes($paymentCostRes);
        } else {
            [$totalPaymentNetto, $totalPaymentGross] = TaxManager::calculateTotalNettoAndGrossFromGrossRes($paymentCostRes);
        }

        if ($updateSubject) {
            $orderPackage
                ->setPaymentCostNet((int) $totalPaymentNetto)
                ->setPaymentCostGross((int) $totalPaymentGross);
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

        if ($subject->getDefaultTypeOrderPackageItems()->count()) {

            /**
             * @var OrderPackageItem $packageItem
             */
            foreach ($subject->getDefaultTypeOrderPackageItems() as $packageItem) {

                $this->recalculatePackageItemValues($packageItem, $nettoCalculation);

                $taxPercentage = $this->calculateTaxPercentage($packageItem, $addTax);

                if ($nettoCalculation) {
                    TaxManager::addValueToNettoRes($taxPercentage, $packageItem->getQuantity() * $packageItem->getPriceNet(), $calculationRes);
                } else {
                    TaxManager::addValueToGrossRes($taxPercentage, $packageItem->getQuantity() * $packageItem->getPriceGross(), $calculationRes);
                }
            }
        }

        return new Result(true, $subject->getOrder()->getCurrency(), 0, 0, $subject, $calculationRes, $calculationRes);
    }

    /**
     * @param OrderPackageItem $orderPackageItem
     * @param bool $nettoCalculation
     * @param bool|null $addTax
     * @return PackageItem
     */
    public function recalculatePackageItemValues(OrderPackageItem $orderPackageItem, bool $nettoCalculation = true, ?bool $addTax = null): PackageItem
    {
//        if ($nettoCalculation && $orderPackageItem->getPriceNet() !== null) {
//            $orderPackageItem->setPriceNet($orderPackageItem->getPriceNet());
//        } elseif (!$nettoCalculation && $orderPackageItem->getPriceGross() !== null) {
//            $orderPackageItem->setPriceGross($orderPackageItem->getPriceGross());
//        }

        $defaultTax = 23; //Fixed for tests

        if ($addTax === null) {
            $addTax = $orderPackageItem->getOrderPackage() && $orderPackageItem->getOrderPackage()->getOrder() ? $orderPackageItem->getOrderPackage()->getOrder()->getVatCalculationType() === OrderInterface::VAT_CALCULATION_TYPE_ADD : true;
        }


        if (!$addTax) {
            $taxPercentage = 0;
        } elseif ($orderPackageItem->getTaxRate() !== null) {
            $taxPercentage = $orderPackageItem->getTaxRate();
        } elseif ($orderPackageItem->getTaxRate() === null && ($calculatedTax = $this->calculateTaxFromPrices($orderPackageItem) !== null)) {
            $taxPercentage = $calculatedTax;
        } elseif ($defaultTax) {
            $taxPercentage = $defaultTax;
        } else {
            $taxPercentage = 0;
        }

        if ($nettoCalculation) {
            if ($orderPackageItem->getPriceNet() !== null && $orderPackageItem->getQuantity() !== null) {
                $orderPackageItem->setValueNet((int) round($orderPackageItem->getQuantity() * $orderPackageItem->getPriceNet()));
            }

            if ($orderPackageItem->getValueNet() !== null && $orderPackageItem->getQuantity() !== null) {
                $orderPackageItem->setValueGross((int) TaxManager::calculateGrossValue($orderPackageItem->getValueNet(), $taxPercentage, true, false, 0));
            }
        } else {
            if ($orderPackageItem->getPriceGross() !== null && $orderPackageItem->getQuantity() !== null) {
                $orderPackageItem->setValueGross((int) round($orderPackageItem->getQuantity() * $orderPackageItem->getPriceGross()));
            }

            if ($orderPackageItem->getValueGross() !== null && $orderPackageItem->getQuantity() !== null) {
                $orderPackageItem->setValueNet((int) TaxManager::calculateNettoValue($orderPackageItem->getValueGross(), $taxPercentage, true, false, 0));
            }
        }

        return $orderPackageItem;
    }

    /**
     * @param OrderPackageItem $orderPackageItem
     * @param bool $nettoCalculation
     * @return void
     */
    public function recalculateOrderPackageItemValues(OrderPackageItem $orderPackageItem, bool $nettoCalculation = true): void
    {
        if ($orderPackageItem->getPriceNet() !== null) {
            $orderPackageItem->setPriceNet((int) round($orderPackageItem->getPriceNet(), 0));
        }

        if ($orderPackageItem->getPriceGross() !== null) {
            $orderPackageItem->setPriceGross((int) round($orderPackageItem->getPriceGross(), 0));
        }

        if ($orderPackageItem->isUpdateValues()) {
            if ($orderPackageItem->getPriceNet() !== null && $orderPackageItem->getQuantity() !== null) {
                $orderPackageItem->setValueNet((int) round($orderPackageItem->getQuantity() * $orderPackageItem->getPriceNet(), 0));
                if ($nettoCalculation || $orderPackageItem->getPriceGross() === null) {
                    $orderPackageItem->setPriceGross((int) round($orderPackageItem->getPriceNet() * ((100 + (int)$orderPackageItem->getTaxRate()) / 100), 0));
                }
            }

            if ($orderPackageItem->getPriceGross() !== null && $orderPackageItem->getQuantity() !== null) {
                $orderPackageItem->setValueGross((int) round($orderPackageItem->getQuantity() * $orderPackageItem->getGrossPrice(), 0));
            }
        }
    }

    /**
     * @param OrderPackageItem $orderPackageItem
     * @param bool $updateItem
     * @return int|null
     */
    public function calculateTaxFromPrices(
        OrderPackageItem $orderPackageItem,
        bool $updateItem = false
    ): ?int {
        if ($orderPackageItem->getPriceGross() && $orderPackageItem->getPriceNet() && $orderPackageItem->getPriceGross() >= $orderPackageItem->getPriceNet()) {
            $tax = (int)round((100 * $orderPackageItem->getPriceNet()) / $orderPackageItem->getPriceNet() - 100, 0);

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
     * @return float|int
     */
    protected function calculateTaxPercentage(OrderPackageItem $orderPackageItem, bool $addTax): float|int
    {
        if (!$addTax) {
            $taxPercentage = 0;
        } elseif ($orderPackageItem->getTaxRate() !== null) {
            $taxPercentage = round($orderPackageItem->getTaxRate(), 2);
        } elseif ($orderPackageItem->getTaxRate() === null && ($calculatedTax = $this->calculateTaxFromPrices($orderPackageItem))) {
            $taxPercentage = $calculatedTax;
        } else {
            $taxPercentage = 23;
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
