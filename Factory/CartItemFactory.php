<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Factory;

use LSB\OrderBundle\Entity\CartItemInterface;
use LSB\UtilityBundle\Factory\BaseFactory;

/**
 * Class CartItemFactory
 * @package LSB\OrderBundle\Factory
 */
class CartItemFactory extends BaseFactory implements CartItemFactoryInterface
{

    /**
     * @return CartItemInterface
     */
    public function createNew(): CartItemInterface
    {
        return parent::createNew();
    }

}
