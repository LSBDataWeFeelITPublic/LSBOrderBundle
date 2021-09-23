<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Calculator;

use Doctrine\ORM\EntityManagerInterface;
use LSB\LocaleBundle\Manager\TaxManager;
use LSB\OrderBundle\Entity\Order;
use LSB\OrderBundle\Entity\OrderInterface;
use LSB\OrderBundle\Entity\OrderPackage;
use LSB\OrderBundle\Entity\OrderPackageInterface;
use LSB\PricelistBundle\Calculator\BaseTotalCalculator;
use LSB\PricelistBundle\Calculator\Result;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Class OrderTotalCalculator
 * @package LSB\OrderBundle\Calculator
 */
class OrderTotalCalculator extends BaseTotalCalculator
{
    protected const SUPPORTED_CLASS = Order::class;

    protected const SUPPORTED_POSITION_CLASS = OrderPackage::class;

    public function __construct(
        EntityManagerInterface $em,
        EventDispatcherInterface $eventDispatcher,
        TokenStorageInterface $tokenStorage
    ) {
        parent::__construct($em, $eventDispatcher, $tokenStorage);
    }

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
            throw new \Exception('Subject must be Order.');
        }

        if (!$subject->getCurrencyIsoCode()) {
            throw new \Exception('Currency ISO code is required for calculations.');
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
            $calculationRes = TaxManager::mergeMoneyRes($calculationRes, $result->getCalculationRes());
            $calculationProductRes = TaxManager::mergeMoneyRes($calculationProductRes, $result->getCalculationProductRes());
            $calculationShippingRes = TaxManager::mergeMoneyRes($calculationShippingRes, $result->getCalculationShippingRes());
            $calculationPaymentCostRest = TaxManager::mergeMoneyRes($calculationPaymentCostRest, $result->getCalculationPaymentCostRes());

            if (!$result->isSuccess()) {
                $canRecalculateTotal = false;
            }
        }

        if ($nettoCalculation) {
            [$totalProductsNet, $totalProductsGross] = TaxManager::calculateMoneyTotalNettoAndGrossFromNettoRes($subject->getCurrencyIsoCode(), $calculationProductRes);
            [$totalShippingNet, $totalShippingGross] = TaxManager::calculateMoneyTotalNettoAndGrossFromNettoRes($subject->getCurrencyIsoCode(), $calculationShippingRes);
            [$totalPaymentCostNet, $totalPaymentCostGross] = TaxManager::calculateMoneyTotalNettoAndGrossFromNettoRes($subject->getCurrencyIsoCode(), $calculationPaymentCostRest);
        } else {
            [$totalProductsNet, $totalProductsGross] = TaxManager::calculateMoneyTotalNettoAndGrossFromGrossRes($subject->getCurrencyIsoCode(), $calculationProductRes);
            [$totalShippingNet, $totalShippingGross] = TaxManager::calculateMoneyTotalNettoAndGrossFromGrossRes($subject->getCurrencyIsoCode(), $calculationShippingRes);
            [$totalPaymentCostNet, $totalPaymentCostGross] = TaxManager::calculateMoneyTotalNettoAndGrossFromGrossRes($subject->getCurrencyIsoCode(), $calculationPaymentCostRest);
        }

        if ($nettoCalculation) {
            [$totalNet, $totalGross] = TaxManager::calculateMoneyTotalNettoAndGrossFromNettoRes($subject->getCurrencyIsoCode(), $calculationRes);
        } else {
            [$totalNet, $totalGross] = TaxManager::calculateMoneyTotalNettoAndGrossFromGrossRes($subject->getCurrencyIsoCode(), $calculationRes);
        }

        if ($updateSubject) {
            $subject
                //Products values
                ->setProductsValueNet($totalProductsNet)
                ->setProductsValueGross($totalProductsGross)
                //Shipping cost values
                ->setShippingCostNet($totalShippingNet)
                ->setShippingCostGross($totalShippingGross)
                //Payment cost values
                ->setPaymentCostNet($totalPaymentCostNet)
                ->setPaymentCostGross($totalPaymentCostGross)
                //Order package total values
                ->setTotalValueNet($totalNet)
                ->setTotalValueGross($totalGross);
        }

        return new Result(
            $canRecalculateTotal,
            $subject->getCurrency(),
            $totalNet,
            $totalGross,
            $subject,
            $calculationRes
        );
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
        return new Result(false, $subject->getCurrency(), null, null, $subject, $res);
    }
}
