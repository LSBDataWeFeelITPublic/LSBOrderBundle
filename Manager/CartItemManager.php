<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Manager;

use LSB\OrderBundle\Entity\CartItemInterface;
use LSB\OrderBundle\Factory\CartItemFactoryInterface;
use LSB\OrderBundle\Repository\CartItemRepositoryInterface;
use LSB\UtilityBundle\Factory\FactoryInterface;
use LSB\UtilityBundle\Form\BaseEntityType;
use LSB\UtilityBundle\Manager\ObjectManagerInterface;
use LSB\UtilityBundle\Manager\BaseManager;
use LSB\UtilityBundle\Repository\RepositoryInterface;

/**
* Class CartItemManager
* @package LSB\OrderBundle\Manager
*/
class CartItemManager extends BaseManager
{

    /**
     * CartItemManager constructor.
     * @param ObjectManagerInterface $objectManager
     * @param CartItemFactoryInterface $factory
     * @param CartItemRepositoryInterface $repository
     * @param BaseEntityType|null $form
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        CartItemFactoryInterface $factory,
        CartItemRepositoryInterface $repository,
        ?BaseEntityType $form
    ) {
        parent::__construct($objectManager, $factory, $repository, $form);
    }

    /**
     * @return CartItemInterface|object
     */
    public function createNew(): CartItemInterface
    {
        return parent::createNew();
    }

    /**
     * @return CartItemFactoryInterface|FactoryInterface
     */
    public function getFactory(): CartItemFactoryInterface
    {
        return parent::getFactory();
    }

    /**
     * @return CartItemRepositoryInterface|RepositoryInterface
     */
    public function getRepository(): CartItemRepositoryInterface
    {
        return parent::getRepository();
    }
}
