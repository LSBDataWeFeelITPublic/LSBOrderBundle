<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Factory;

use LSB\OrderBundle\Entity\OrderInterface;
use LSB\UtilityBundle\Factory\BaseFactory;

/**
 * Class OrderFactory
 * @package LSB\OrderBundle\Factory
 */
class OrderFactory extends BaseFactory implements OrderFactoryInterface
{

    /**
     * @return OrderInterface
     */
    public function createNew(): OrderInterface
    {
        return parent::createNew();
    }

}
