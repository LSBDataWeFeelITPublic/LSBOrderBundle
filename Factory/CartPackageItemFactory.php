<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Factory;

use LSB\OrderBundle\Entity\CartPackageItemInterface;
use LSB\UtilityBundle\Factory\BaseFactory;

/**
 * Class CartPackageItemFactory
 * @package LSB\OrderBundle\Factory
 */
class CartPackageItemFactory extends BaseFactory implements CartPackageItemFactoryInterface
{

    /**
     * @return CartPackageItemInterface
     */
    public function createNew(): CartPackageItemInterface
    {
        return parent::createNew();
    }

}
