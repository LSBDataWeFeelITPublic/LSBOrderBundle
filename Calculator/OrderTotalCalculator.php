<?php

namespace LSB\OrderBundle\Calculator;

use LSB\CustomerBundle\Service\TaxManager;
use LSB\OrderBundle\Entity\Order;
use LSB\OrderBundle\Entity\OrderPackage;
use LSB\OrderBundle\Entity\PackageItem;
use LSB\UtilBundle\Calculator\BaseTotalCalculator;
use LSB\UtilBundle\Calculator\CalculatorResult;
use LSB\UtilBundle\Entity\Application;

/**
 * Class OrderTotalCalculator
 * @package LSB\OrderBundle\Calculator
 */
class OrderTotalCalculator extends BaseTotalCalculator
{
    protected const SUPPORTED_CLASS = Order::class;

    protected const SUPPORTED_POSITION_CLASS = PackageItem::class;

    /**
     * @param $subject
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
        if (!$subject instanceof Order) {
            throw new \Exception('Subject must be Order');
        }

        $calculationRes = [];
        $calculationProductRes = [];
        $calculationShippingRes = [];
        $calculationPaymentCostRest = [];

        $nettoCalculation = $subject->getCalculationType() === Order::CALCULATION_TYPE_GROSS ? false : true;
        $canRecalculateTotal = true;

        /**
         * @var OrderPackage $orderPackage
         */
        foreach ($subject->getPackages() as $orderPackage) {
            //Po przeliczeniu wartości pozycji
            $result = $this->totalCalculatorManager->calculateTotal($orderPackage, $options, $application, BaseTotalCalculator::NAME);
            $calculationRes = TaxManager::mergeRes($calculationRes, $result->getCalculationRes());
            $calculationProductRes = TaxManager::mergeRes($calculationProductRes, $result->getCalculationProductRes());
            $calculationShippingRes = TaxManager::mergeRes($calculationShippingRes, $result->getCalculationShippingRes());
            $calculationPaymentCostRest = TaxManager::mergeRes($calculationPaymentCostRest, $result->getCalculationPaymentCostRes());

            if (!$result->isSuccess()) {
                $canRecalculateTotal = false;
            }
        }

        //Wyliczamy wartość produktów

        if ($nettoCalculation) {
            list($totalProductsNet, $totalProductsGross) = TaxManager::calculateTotalNettoAndGrossFromNettoRes($calculationProductRes);
            list($totalShippingNet, $totalShippingGross) = TaxManager::calculateTotalNettoAndGrossFromNettoRes($calculationShippingRes);
            list($totalPaymentCostNet, $totalPaymentCostGross) = TaxManager::calculateTotalNettoAndGrossFromNettoRes($calculationPaymentCostRest);
        } else {
            list($totalProductsNet, $totalProductsGross) = TaxManager::calculateTotalNettoAndGrossFromGrossRes($calculationProductRes);
            list($totalShippingNet, $totalShippingGross) = TaxManager::calculateTotalNettoAndGrossFromGrossRes($calculationShippingRes);
            list($totalPaymentCostNet, $totalPaymentCostGross) = TaxManager::calculateTotalNettoAndGrossFromGrossRes($calculationPaymentCostRest);
        }

        //Doliczamy koszt wysyłki i dodatkowe opłaty
        //Koszt dostawy na orderze to suma kosztow dostawy z paczek
        //W związku z tym usuwam uzupełnianie kosztu dostawy z paczki o koszt dostawy z zamówienia
        //Zostaje naliczanie zbiorcze dopłaty za wybór metody płatności
        if ($nettoCalculation) {
            TaxManager::addValueToNettoRes($this->ps->getParameter('default.tax'), (float)$subject->getTotalPaymentPrice(), $calculationRes);
        } else {
            TaxManager::addValueToNettoRes($this->ps->getParameter('default.tax'), (float)$subject->getTotalPaymentPriceGross(), $calculationRes);
        }

        if ($nettoCalculation) {
            list($totalNet, $totalGross) = TaxManager::calculateTotalNettoAndGrossFromNettoRes($calculationRes);
        } else {
            list($totalNet, $totalGross) = TaxManager::calculateTotalNettoAndGrossFromGrossRes($calculationRes);
        }

        if ($updateSubject) {
            $subject
                ->setTotalPaymentCost($totalPaymentCostNet)
                ->setTotalPaymentCostGross($totalPaymentCostGross)
                ->setTotalShipping($totalShippingNet)
                ->setTotalShippingGross($totalShippingGross)
                ->setTotal($totalNet)
                ->setTotalGross($totalGross)
                ->setTotalProducts($totalProductsNet)
                ->setTotalProductsGross($totalProductsGross);
        }

        return new CalculatorResult($canRecalculateTotal, $subject->getCurrencyRelation(), $totalNet, $totalGross, $subject, $calculationRes);
    }

    /**
     * Zamówienie nie posiada pozycji, należy skorzystać z kalkulatora orderPackage
     *
     * @param $subject
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
        throw new \Exception('Not supported');
    }
}
