<?php

namespace LSB\OrderBundle\Calculator;

use LSB\CustomerBundle\Service\TaxManager;
use LSB\OrderBundle\Entity\Order;
use LSB\OrderBundle\Entity\OrderPackage;
use LSB\OrderBundle\Entity\PackageItem;
use LSB\ProductBundle\Interfaces\ProductTypeInterface;
use LSB\UtilBundle\Calculator\BaseTotalCalculator;
use LSB\UtilBundle\Calculator\CalculatorResult;
use LSB\UtilBundle\Entity\Application;

/**
 * Class OrderPackageTotalCalculator
 * @package LSB\OrderBundle\Calculator
 */
class OrderPackageTotalCalculator extends BaseTotalCalculator
{
    protected const SUPPORTED_CLASS = OrderPackage::class;

    protected const SUPPORTED_POSITION_CLASS = PackageItem::class;

    /**
     * @param OrderPackage|null $subject
     * @param array $options
     * @param Application $application
     * @param bool $updateSubject
     * @param bool $updatePositions
     * @param array $calculationRes
     * @return CalculatorResult
     * @throws \Exception
     */
    public function calculateTotal(
        $subject,
        array $options,
        Application $application,
        bool $updateSubject = true,
        bool $updatePositions = true,
        array &$calculationRes = []
    ): CalculatorResult {
        if (!$subject instanceof OrderPackage) {
            throw new \Exception('Subject must be OrderPackage');
        }

        $nettoCalculation = $subject->getOrder() && $subject->getOrder()->getCalculationType() === Order::CALCULATION_TYPE_GROSS ? false : true;

        $calculationProductsRes = [];
        $calculationShippingRes = [];
        $calculationPaymentCostRes = [];

        $positionCalculationResult = $this->calculatePositions($subject, $options, $application, $updatePositions);

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

        if ($nettoCalculation) {
            [$totalNet, $totalGross] = TaxManager::calculateTotalNettoAndGrossFromNettoRes($calculationRes);
        } else {
            [$totalNet, $totalGross] = TaxManager::calculateTotalNettoAndGrossFromGrossRes($calculationRes);
        }

        if ($updateSubject) {
            $subject
                ->setTotalNetto($totalNet)
                ->setTotalGross($totalGross)
                ->setTotalProducts($totalProductsNet)
                ->setTotalProductsGross($totalProductsGross);
        }

        $result = new CalculatorResult(
            true,
            $subject->getOrder()->getCurrencyRelation(),
            $totalProductsNet,
            $totalProductsGross,
            $subject,
            $calculationRes,
            $calculationProductsRes,
            $calculationShippingRes,
            $calculationPaymentCostRes
        );

        return $result;
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
        //Dokonujemy aktualizacji wartości kosztu przesyłki w nagłówku
        $shippingPackageItems = $orderPackage->getShippingServicePackageItems();

        $addTax = $orderPackage->getOrder() ? $orderPackage->getOrder()->getAddVat() : true;

        /**
         * @var PackageItem $shippingPackageItem
         */
        foreach ($shippingPackageItems as $shippingPackageItem) {
            if ($addTax) {
                $taxPercentage = $shippingPackageItem->getTaxPercentage();
            } else {
                $taxPercentage = null;
            }

            if ($shippingPackageItem->getTaxPercentage() !== $taxPercentage) {
                $shippingPackageItem->setTaxPercentage($taxPercentage);
            }

            if ($nettoCalculation) {
                TaxManager::addValueToNettoRes($taxPercentage, (float)$shippingPackageItem->getNettoValue(), $shippingCostRes);
                TaxManager::addValueToNettoRes($taxPercentage, (float)$shippingPackageItem->getNettoValue(), $calculationRes);
            } else {
                TaxManager::addValueToGrossRes($taxPercentage, (float)$shippingPackageItem->getGrossValue(), $shippingCostRes);
                TaxManager::addValueToGrossRes($taxPercentage, (float)$shippingPackageItem->getGrossValue(), $calculationRes);
            }
        }

        if ($nettoCalculation) {
            [$totalShippingNetto, $totalShippingGross] = TaxManager::calculateTotalNettoAndGrossFromNettoRes($shippingCostRes);
        } else {
            [$totalShippingNetto, $totalShippingGross] = TaxManager::calculateTotalNettoAndGrossFromGrossRes($shippingCostRes);
        }

        if ($updateSubject) {
            $orderPackage
                ->setTotalShipping($totalShippingNetto)
                ->setTotalShippingGross($totalShippingGross)
                ->setTotalPaymentCost((float) 0) //Aktualnie brak wsparcia, koszt płatności doliczany jest do wartości usługi wysyłki
                ->setTotalPaymentCostGross((float) 0);
        }
    }

    /**
     * @param OrderPackage|null $subject
     * @param array $options
     * @param Application $application
     * @param bool $updatePositions
     * @return CalculatorResult
     * @throws \Exception
     */
    public function calculatePositions(
        $subject,
        array $options,
        Application $application,
        bool $updatePositions = true
    ): CalculatorResult {
        if (!$subject instanceof OrderPackage) {
            throw new \Exception('Wrong calculation subject');
        }

        $nettoCalculation = $subject->getOrder() && $subject->getOrder()->getCalculationType() === Order::CALCULATION_TYPE_GROSS ? false : true;
        $calculationRes = [];

        $addTax = $subject->getOrder() ? $subject->getOrder()->getAddVat() : true;


        if ($subject->getItems()->count()) {

            /**
             * @var PackageItem $packageItem
             */
            foreach ($subject->getItems() as $packageItem) {
                $packageItem->recalculateValues($nettoCalculation);

                $this->recalculatePackageItemValues($packageItem, $nettoCalculation);

                $calculatedTax = $packageItem->calculateTaxFromPrices();

                if (!$addTax) {
                    $taxPercentage = 0;
                } elseif ($packageItem->getTaxPercentage() !== null) {
                    $taxPercentage = round($packageItem->getTaxPercentage(), 2);
                } elseif ($packageItem->getTaxPercentage() === null && $calculatedTax !== null) {
                    $taxPercentage = $calculatedTax;
                } else {
                    $taxPercentage = $this->ps->getParameter('default.tax');
                }

                //Jeżeli mamy do czynienia z pozycją typu SHIPPING_SERVICE dokonujemy pominięcia przy zliczaniu wartości pozycji jako produktów
                if ($packageItem->getProductType() === ProductTypeInterface::TYPE_SERVICE_SHIPPING) {
                    continue;
                }

                if ($nettoCalculation) {
                    TaxManager::addValueToNettoRes($taxPercentage, $packageItem->getQuantity() * $packageItem->getNettoPrice(), $calculationRes);
                } else {
                    TaxManager::addValueToGrossRes($taxPercentage, $packageItem->getQuantity() * $packageItem->getGrossPrice(), $calculationRes);
                }
            }
        }

        return new CalculatorResult(true, $subject->getOrder()->getCurrencyRelation(), 0, 0, $subject, $calculationRes, $calculationRes);
    }

    /**
     * @param PackageItem $packageItem
     * @param bool $nettoCalculation
     * @param bool|null $addTax
     * @return PackageItem
     */
    public function recalculatePackageItemValues(PackageItem $packageItem, bool $nettoCalculation = true, ?bool $addTax = null): PackageItem
    {
        //Dla pewności zaokrąglamy wartości netto lub brutto
        if ($nettoCalculation && $packageItem->getNettoPrice() !== null) {
            $packageItem->setNettoPrice(round($packageItem->getNettoPrice(), 2));
        } elseif (!$nettoCalculation && $packageItem->getGrossPrice() !== null) {
            $packageItem->setGrossPrice(round($packageItem->getGrossPrice(), 2));
        }

        $defaultTax = $this->ps->getParameter('default.tax');
        //uwzględnianie stawki vat
        if ($addTax === null) {
            $addTax = $packageItem->getOrderPackage() && $packageItem->getOrderPackage()->getOrder() ? $packageItem->getOrderPackage()->getOrder()->getAddVat() : true;
        }

        if (!$addTax) {
            $taxPercentage = 0;
        } elseif ($packageItem->getTaxPercentage() !== null) {
            $taxPercentage = $packageItem->getTaxPercentage();
        } elseif ($packageItem->getTaxPercentage() === null && ($calculatedTax = $packageItem->calculateTaxFromPrices()) !== null) {
            $taxPercentage = $calculatedTax;
        } elseif ($defaultTax) {
            $taxPercentage = $defaultTax;
        } else {
            $taxPercentage = 0;
        }

        if ($nettoCalculation) {
            if ($packageItem->getNettoPrice() !== null && $packageItem->getQuantity() !== null) {
                $packageItem->setNettoValue(round($packageItem->getQuantity() * $packageItem->getNettoPrice(), 2));
            }

            $packageItem->setGrossValue(TaxManager::calculateGrossValue($packageItem->getNettoValue(), $taxPercentage, true));
        } else {
            if ($packageItem->getGrossPrice() !== null && $packageItem->getQuantity() !== null) {
                $packageItem->setGrossValue(round($packageItem->getQuantity() * $packageItem->getGrossPrice(), 2));
            }

            $packageItem->setNettoValue(TaxManager::calculateNettoValue($packageItem->getGrossValue(), $taxPercentage, true));
        }

        return $packageItem;
    }
}
