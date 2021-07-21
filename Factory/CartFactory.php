<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Factory;

use LSB\OrderBundle\Entity\CartInterface;
use LSB\UtilityBundle\Factory\BaseFactory;

/**
 * Class CartFactory
 * @package LSB\OrderBundle\Factory
 */
class CartFactory extends BaseFactory implements CartFactoryInterface
{

    /**
     * @return CartInterface
     */
    public function createNew(): CartInterface
    {
        return parent::createNew();
    }

}
