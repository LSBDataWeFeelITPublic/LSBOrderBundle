<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Factory;

use LSB\OrderBundle\Entity\CartPackageInterface;
use LSB\UtilityBundle\Factory\BaseFactory;

/**
 * Class CartPackageFactory
 * @package LSB\OrderBundle\Factory
 */
class CartPackageFactory extends BaseFactory implements CartPackageFactoryInterface
{

    /**
     * @return CartPackageInterface
     */
    public function createNew(): CartPackageInterface
    {
        return parent::createNew();
    }

}
