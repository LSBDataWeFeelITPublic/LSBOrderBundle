<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Manager;

use LSB\OrderBundle\Entity\CartPackageItemInterface;
use LSB\OrderBundle\Factory\CartPackageItemFactoryInterface;
use LSB\OrderBundle\Repository\CartPackageItemRepositoryInterface;
use LSB\UtilityBundle\Factory\FactoryInterface;
use LSB\UtilityBundle\Form\BaseEntityType;
use LSB\UtilityBundle\Manager\ObjectManagerInterface;
use LSB\UtilityBundle\Manager\BaseManager;
use LSB\UtilityBundle\Repository\RepositoryInterface;

/**
* Class CartPackageItemManager
* @package LSB\OrderBundle\Manager
*/
class CartPackageItemManager extends BaseManager
{

    /**
     * CartPackageItemManager constructor.
     * @param ObjectManagerInterface $objectManager
     * @param CartPackageItemFactoryInterface $factory
     * @param CartPackageItemRepositoryInterface $repository
     * @param BaseEntityType|null $form
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        CartPackageItemFactoryInterface $factory,
        CartPackageItemRepositoryInterface $repository,
        ?BaseEntityType $form
    ) {
        parent::__construct($objectManager, $factory, $repository, $form);
    }

    /**
     * @return CartPackageItemInterface|object
     */
    public function createNew(): CartPackageItemInterface
    {
        return parent::createNew();
    }

    /**
     * @return CartPackageItemFactoryInterface|FactoryInterface
     */
    public function getFactory(): CartPackageItemFactoryInterface
    {
        return parent::getFactory();
    }

    /**
     * @return CartPackageItemRepositoryInterface|RepositoryInterface
     */
    public function getRepository(): CartPackageItemRepositoryInterface
    {
        return parent::getRepository();
    }
}
