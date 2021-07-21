<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Repository;

use Doctrine\Persistence\ManagerRegistry;
use LSB\OrderBundle\Entity\Cart;
use LSB\UtilityBundle\Repository\BaseRepository;
use LSB\UtilityBundle\Repository\PaginationRepositoryTrait;

/**
 * Class CartRepository
 * @package LSB\OrderBundle\Repository
 */
class CartRepository extends BaseRepository implements CartRepositoryInterface
{
    use PaginationRepositoryTrait;

    /**
     * CartRepository constructor.
     * @param ManagerRegistry $registry
     * @param string|null $stringClass
     */
    public function __construct(ManagerRegistry $registry, ?string $stringClass = null)
    {
        parent::__construct($registry, $stringClass ?? Cart::class);
    }

}
