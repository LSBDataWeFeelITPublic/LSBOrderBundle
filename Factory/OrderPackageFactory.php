<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Factory;

use LSB\OrderBundle\Entity\OrderPackageInterface;
use LSB\UtilityBundle\Factory\BaseFactory;

/**
 * Class OrderPackageFactory
 * @package LSB\OrderBundle\Factory
 */
class OrderPackageFactory extends BaseFactory implements OrderPackageFactoryInterface
{

    /**
     * @return OrderPackageInterface
     */
    public function createNew(): OrderPackageInterface
    {
        return parent::createNew();
    }

}
