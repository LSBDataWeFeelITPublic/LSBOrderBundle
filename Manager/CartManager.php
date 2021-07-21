<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Manager;

use LSB\OrderBundle\Entity\CartInterface;
use LSB\OrderBundle\Factory\CartFactoryInterface;
use LSB\OrderBundle\Repository\CartRepositoryInterface;
use LSB\UtilityBundle\Factory\FactoryInterface;
use LSB\UtilityBundle\Form\BaseEntityType;
use LSB\UtilityBundle\Manager\ObjectManagerInterface;
use LSB\UtilityBundle\Manager\BaseManager;
use LSB\UtilityBundle\Repository\RepositoryInterface;

/**
* Class CartManager
* @package LSB\OrderBundle\Manager
*/
class CartManager extends BaseManager
{

    /**
     * CartManager constructor.
     * @param ObjectManagerInterface $objectManager
     * @param CartFactoryInterface $factory
     * @param CartRepositoryInterface $repository
     * @param BaseEntityType|null $form
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        CartFactoryInterface $factory,
        CartRepositoryInterface $repository,
        ?BaseEntityType $form
    ) {
        parent::__construct($objectManager, $factory, $repository, $form);
    }

    /**
     * @return CartInterface|object
     */
    public function createNew(): CartInterface
    {
        return parent::createNew();
    }

    /**
     * @return CartFactoryInterface|FactoryInterface
     */
    public function getFactory(): CartFactoryInterface
    {
        return parent::getFactory();
    }

    /**
     * @return CartRepositoryInterface|RepositoryInterface
     */
    public function getRepository(): CartRepositoryInterface
    {
        return parent::getRepository();
    }
}
