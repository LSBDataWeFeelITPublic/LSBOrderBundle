<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Repository;

use Doctrine\Persistence\ManagerRegistry;
use LSB\OrderBundle\Entity\CartItem;
use LSB\UtilityBundle\Repository\BaseRepository;
use LSB\UtilityBundle\Repository\PaginationRepositoryTrait;

/**
 * Class CartItemRepository
 * @package LSB\OrderBundle\Repository
 */
class CartItemRepository extends BaseRepository implements CartItemRepositoryInterface
{
    use PaginationRepositoryTrait;

    /**
     * CartItemRepository constructor.
     * @param ManagerRegistry $registry
     * @param string|null $stringClass
     */
    public function __construct(ManagerRegistry $registry, ?string $stringClass = null)
    {
        parent::__construct($registry, $stringClass ?? CartItem::class);
    }

}
