<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Factory;

use LSB\OrderBundle\Entity\OrderNoteInterface;
use LSB\UtilityBundle\Factory\BaseFactory;

/**
 * Class OrderNoteFactory
 * @package LSB\OrderBundle\Factory
 */
class OrderNoteFactory extends BaseFactory implements OrderNoteFactoryInterface
{

    /**
     * @return OrderNoteInterface
     */
    public function createNew(): OrderNoteInterface
    {
        return parent::createNew();
    }

}
