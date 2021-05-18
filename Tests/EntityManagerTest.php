<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Tests;

use LSB\OrderBundle\Entity\EntityInterface;
use LSB\OrderBundle\Factory\EntityFactory;
use LSB\OrderBundle\Factory\EntityFactoryInterface;
use LSB\OrderBundle\Manager\EntityManager;
use LSB\OrderBundle\Repository\EntityRepository;
use LSB\OrderBundle\Repository\EntityRepositoryInterface;
use LSB\UtilityBundle\Manager\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * Class EntityManagerTest
 * @package LSB\OrderBundle\Tests
 */
class EntityManagerTest extends TestCase
{
    /**
     * Assert returned interfaces
     * @throws \Exception
     */
    public function testReturnedInterfaces()
    {
        $objectManagerMock = $this->createMock(ObjectManager::class);
        $entityFactoryMock = $this->createMock(EntityFactory::class);
        $entityRepositoryMock = $this->createMock(EntityRepository::class);

        $entityManager = new EntityManager($objectManagerMock, $entityFactoryMock, $entityRepositoryMock, null);

        $this->assertInstanceOf(EntityInterface::class, $entityManager->createNew());
        $this->assertInstanceOf(EntityFactoryInterface::class, $entityManager->getFactory());
        $this->assertInstanceOf(EntityRepositoryInterface::class, $entityManager->getRepository());
    }
}
