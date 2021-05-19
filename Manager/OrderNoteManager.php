<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Manager;

use LSB\OrderBundle\Entity\OrderNoteInterface;
use LSB\OrderBundle\Factory\OrderNoteFactoryInterface;
use LSB\OrderBundle\Repository\OrderNoteRepositoryInterface;
use LSB\UtilityBundle\Factory\FactoryInterface;
use LSB\UtilityBundle\Form\BaseEntityType;
use LSB\UtilityBundle\Manager\ObjectManagerInterface;
use LSB\UtilityBundle\Manager\BaseManager;
use LSB\UtilityBundle\Repository\RepositoryInterface;

/**
* Class OrderNoteManager
* @package LSB\OrderBundle\Manager
*/
class OrderNoteManager extends BaseManager
{

    /**
     * OrderNoteManager constructor.
     * @param ObjectManagerInterface $objectManager
     * @param OrderNoteFactoryInterface $factory
     * @param OrderNoteRepositoryInterface $repository
     * @param BaseEntityType|null $form
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        OrderNoteFactoryInterface $factory,
        OrderNoteRepositoryInterface $repository,
        ?BaseEntityType $form
    ) {
        parent::__construct($objectManager, $factory, $repository, $form);
    }

    /**
     * @return OrderNoteInterface|object
     */
    public function createNew(): OrderNoteInterface
    {
        return parent::createNew();
    }

    /**
     * @return OrderNoteFactoryInterface|FactoryInterface
     */
    public function getFactory(): OrderNoteFactoryInterface
    {
        return parent::getFactory();
    }

    /**
     * @return OrderNoteRepositoryInterface|RepositoryInterface
     */
    public function getRepository(): OrderNoteRepositoryInterface
    {
        return parent::getRepository();
    }
}
