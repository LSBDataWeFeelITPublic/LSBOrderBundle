<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Factory;

use LSB\OrderBundle\Entity\OrderPackageItemInterface;
use LSB\UtilityBundle\Factory\BaseFactory;

/**
 * Class OrderPackageItemFactory
 * @package LSB\OrderBundle\Factory
 */
class OrderPackageItemFactory extends BaseFactory implements OrderPackageItemFactoryInterface
{

    /**
     * @return OrderPackageItemInterface
     */
    public function createNew(): OrderPackageItemInterface
    {
        return parent::createNew();
    }

}
