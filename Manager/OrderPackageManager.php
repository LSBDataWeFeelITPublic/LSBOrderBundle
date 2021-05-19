<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Manager;

use LSB\OrderBundle\Entity\OrderPackageInterface;
use LSB\OrderBundle\Factory\OrderPackageFactoryInterface;
use LSB\OrderBundle\Repository\OrderPackageRepositoryInterface;
use LSB\UtilityBundle\Factory\FactoryInterface;
use LSB\UtilityBundle\Form\BaseEntityType;
use LSB\UtilityBundle\Manager\ObjectManagerInterface;
use LSB\UtilityBundle\Manager\BaseManager;
use LSB\UtilityBundle\Repository\RepositoryInterface;

/**
* Class OrderPackageManager
* @package LSB\OrderBundle\Manager
*/
class OrderPackageManager extends BaseManager
{

    /**
     * OrderPackageManager constructor.
     * @param ObjectManagerInterface $objectManager
     * @param OrderPackageFactoryInterface $factory
     * @param OrderPackageRepositoryInterface $repository
     * @param BaseEntityType|null $form
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        OrderPackageFactoryInterface $factory,
        OrderPackageRepositoryInterface $repository,
        ?BaseEntityType $form
    ) {
        parent::__construct($objectManager, $factory, $repository, $form);
    }

    /**
     * @return OrderPackageInterface|object
     */
    public function createNew(): OrderPackageInterface
    {
        return parent::createNew();
    }

    /**
     * @return OrderPackageFactoryInterface|FactoryInterface
     */
    public function getFactory(): OrderPackageFactoryInterface
    {
        return parent::getFactory();
    }

    /**
     * @return OrderPackageRepositoryInterface|RepositoryInterface
     */
    public function getRepository(): OrderPackageRepositoryInterface
    {
        return parent::getRepository();
    }
}
