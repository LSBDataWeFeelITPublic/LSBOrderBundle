<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Calculator;

use LSB\LocaleBundle\Manager\TaxManager;
use LSB\OrderBundle\Entity\Order;
use LSB\OrderBundle\Entity\OrderInterface;
use LSB\OrderBundle\Entity\OrderPackage;
use LSB\OrderBundle\Entity\OrderPackageInterface;
use LSB\PricelistBundle\Calculator\BaseTotalCalculator;
use LSB\PricelistBundle\Calculator\Result;

/**
 * Class OrderTotalCalculator
 * @package LSB\OrderBundle\Calculator
 */
class OrderTotalCalculator extends BaseTotalCalculator
{
    protected const SUPPORTED_CLASS = Order::class;

    protected const SUPPORTED_POSITION_CLASS = OrderPackage::class;

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
    public function calculateTotal($subject, array $options = [], ?string $applicationCode = null, bool $updateSubject = true, bool $updatePositions = true, array &$calculationRes = []): Result
    {
        if (!$subject instanceof Order) {
            throw new \Exception('Subject must be Order');
        }

        $calculationRes = [];
        $calculationProductRes = [];
        $calculationShippingRes = [];
        $calculationPaymentCostRest = [];

        $nettoCalculation = $subject->getCalculationType() === OrderInterface::CALCULATION_TYPE_NET;
        $canRecalculateTotal = true;

        /**
         * @var OrderPackageInterface $orderPackage
         */
        foreach ($subject->getOrderPackages() as $orderPackage) {
            $result = $this->totalCalculatorManager->calculateTotal($orderPackage, $options, 'admin', BaseTotalCalculator::NAME);
            $calculationRes = TaxManager::mergeRes($calculationRes, $result->getCalculationRes());
            $calculationProductRes = TaxManager::mergeRes($calculationProductRes, $result->getCalculationProductRes());
            $calculationShippingRes = TaxManager::mergeRes($calculationShippingRes, $result->getCalculationShippingRes());
            $calculationPaymentCostRest = TaxManager::mergeRes($calculationPaymentCostRest, $result->getCalculationPaymentCostRes());

            if (!$result->isSuccess()) {
                $canRecalculateTotal = false;
            }
        }

        if ($nettoCalculation) {
            [$totalProductsNet, $totalProductsGross] = TaxManager::calculateTotalNettoAndGrossFromNettoRes($calculationProductRes);
            [$totalShippingNet, $totalShippingGross] = TaxManager::calculateTotalNettoAndGrossFromNettoRes($calculationShippingRes);
            [$totalPaymentCostNet, $totalPaymentCostGross] = TaxManager::calculateTotalNettoAndGrossFromNettoRes($calculationPaymentCostRest);
        } else {
            [$totalProductsNet, $totalProductsGross] = TaxManager::calculateTotalNettoAndGrossFromGrossRes($calculationProductRes);
            [$totalShippingNet, $totalShippingGross] = TaxManager::calculateTotalNettoAndGrossFromGrossRes($calculationShippingRes);
            [$totalPaymentCostNet, $totalPaymentCostGross] = TaxManager::calculateTotalNettoAndGrossFromGrossRes($calculationPaymentCostRest);
        }

        if ($nettoCalculation) {
            [$totalNet, $totalGross] = TaxManager::calculateTotalNettoAndGrossFromNettoRes($calculationRes);
        } else {
            [$totalNet, $totalGross] = TaxManager::calculateTotalNettoAndGrossFromGrossRes($calculationRes);
        }

        if ($updateSubject) {
            $subject
                //Products values
                ->setProductsValueNet((int)$totalProductsNet)
                ->setProductsValueGross((int)$totalProductsGross)
                //Shipping cost values
                ->setShippingCostNet((int)$totalShippingNet)
                ->setShippingCostGross((int)$totalShippingGross)
                //Payment cost values
                ->setPaymentCostNet((int)$totalPaymentCostNet)
                ->setPaymentCostGross((int)$totalPaymentCostGross)
                //Order package total values
                ->setTotalValueNet((int)$totalNet)
                ->setTotalValueGross((int)$totalGross);
        }

        return new Result($canRecalculateTotal, $subject->getCurrency(), $totalNet, $totalGross, $subject, $calculationRes);
    }

    /**
     * @param Order $subject
     * @param array $options
     * @param string|null $applicationCode
     * @param bool $updatePositions
     * @return Result
     */
    public function calculatePositions($subject, array $options, ?string $applicationCode, bool $updatePositions = true): Result
    {
        $res = [];
        return new Result(false, $subject->getCurrency(), 0, 0, $subject, $res);
    }
}
