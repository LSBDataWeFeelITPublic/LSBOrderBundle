<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Manager;

use LSB\OrderBundle\Entity\CartPackageInterface;
use LSB\OrderBundle\Factory\CartPackageFactoryInterface;
use LSB\OrderBundle\Repository\CartPackageRepositoryInterface;
use LSB\UtilityBundle\Factory\FactoryInterface;
use LSB\UtilityBundle\Form\BaseEntityType;
use LSB\UtilityBundle\Manager\ObjectManagerInterface;
use LSB\UtilityBundle\Manager\BaseManager;
use LSB\UtilityBundle\Repository\RepositoryInterface;

/**
* Class CartPackageManager
* @package LSB\OrderBundle\Manager
*/
class CartPackageManager extends BaseManager
{

    /**
     * CartPackageManager constructor.
     * @param ObjectManagerInterface $objectManager
     * @param CartPackageFactoryInterface $factory
     * @param CartPackageRepositoryInterface $repository
     * @param BaseEntityType|null $form
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        CartPackageFactoryInterface $factory,
        CartPackageRepositoryInterface $repository,
        ?BaseEntityType $form
    ) {
        parent::__construct($objectManager, $factory, $repository, $form);
    }

    /**
     * @return CartPackageInterface|object
     */
    public function createNew(): CartPackageInterface
    {
        return parent::createNew();
    }

    /**
     * @return CartPackageFactoryInterface|FactoryInterface
     */
    public function getFactory(): CartPackageFactoryInterface
    {
        return parent::getFactory();
    }

    /**
     * @return CartPackageRepositoryInterface|RepositoryInterface
     */
    public function getRepository(): CartPackageRepositoryInterface
    {
        return parent::getRepository();
    }
}
