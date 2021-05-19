<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Manager;

use LSB\OrderBundle\Entity\OrderPackageItemInterface;
use LSB\OrderBundle\Factory\OrderPackageItemFactoryInterface;
use LSB\OrderBundle\Repository\OrderPackageItemRepositoryInterface;
use LSB\UtilityBundle\Factory\FactoryInterface;
use LSB\UtilityBundle\Form\BaseEntityType;
use LSB\UtilityBundle\Manager\ObjectManagerInterface;
use LSB\UtilityBundle\Manager\BaseManager;
use LSB\UtilityBundle\Repository\RepositoryInterface;

/**
* Class OrderPackageItemManager
* @package LSB\OrderBundle\Manager
*/
class OrderPackageItemManager extends BaseManager
{

    /**
     * OrderPackageItemManager constructor.
     * @param ObjectManagerInterface $objectManager
     * @param OrderPackageItemFactoryInterface $factory
     * @param OrderPackageItemRepositoryInterface $repository
     * @param BaseEntityType|null $form
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        OrderPackageItemFactoryInterface $factory,
        OrderPackageItemRepositoryInterface $repository,
        ?BaseEntityType $form
    ) {
        parent::__construct($objectManager, $factory, $repository, $form);
    }

    /**
     * @return OrderPackageItemInterface|object
     */
    public function createNew(): OrderPackageItemInterface
    {
        return parent::createNew();
    }

    /**
     * @return OrderPackageItemFactoryInterface|FactoryInterface
     */
    public function getFactory(): OrderPackageItemFactoryInterface
    {
        return parent::getFactory();
    }

    /**
     * @return OrderPackageItemRepositoryInterface|RepositoryInterface
     */
    public function getRepository(): OrderPackageItemRepositoryInterface
    {
        return parent::getRepository();
    }
}
