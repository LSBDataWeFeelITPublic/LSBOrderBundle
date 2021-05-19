<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Manager;

use LSB\OrderBundle\Entity\OrderInterface;
use LSB\OrderBundle\Factory\OrderFactoryInterface;
use LSB\OrderBundle\Repository\OrderRepositoryInterface;
use LSB\UtilityBundle\Factory\FactoryInterface;
use LSB\UtilityBundle\Form\BaseEntityType;
use LSB\UtilityBundle\Manager\ObjectManagerInterface;
use LSB\UtilityBundle\Manager\BaseManager;
use LSB\UtilityBundle\Repository\RepositoryInterface;

/**
* Class OrderManager
* @package LSB\OrderBundle\Manager
*/
class OrderManager extends BaseManager
{

    /**
     * OrderManager constructor.
     * @param ObjectManagerInterface $objectManager
     * @param OrderFactoryInterface $factory
     * @param OrderRepositoryInterface $repository
     * @param BaseEntityType|null $form
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        OrderFactoryInterface $factory,
        OrderRepositoryInterface $repository,
        ?BaseEntityType $form
    ) {
        parent::__construct($objectManager, $factory, $repository, $form);
    }

    /**
     * @return OrderInterface|object
     */
    public function createNew(): OrderInterface
    {
        return parent::createNew();
    }

    /**
     * @return OrderFactoryInterface|FactoryInterface
     */
    public function getFactory(): OrderFactoryInterface
    {
        return parent::getFactory();
    }

    /**
     * @return OrderRepositoryInterface|RepositoryInterface
     */
    public function getRepository(): OrderRepositoryInterface
    {
        return parent::getRepository();
    }
}
