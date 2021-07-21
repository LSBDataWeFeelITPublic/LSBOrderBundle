<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Repository;

use Doctrine\Persistence\ManagerRegistry;
use LSB\OrderBundle\Entity\CartPackageItem;
use LSB\UtilityBundle\Repository\BaseRepository;
use LSB\UtilityBundle\Repository\PaginationRepositoryTrait;

/**
 * Class CartPackageItemRepository
 * @package LSB\OrderBundle\Repository
 */
class CartPackageItemRepository extends BaseRepository implements CartPackageItemRepositoryInterface
{
    use PaginationRepositoryTrait;

    /**
     * CartPackageItemRepository constructor.
     * @param ManagerRegistry $registry
     * @param string|null $stringClass
     */
    public function __construct(ManagerRegistry $registry, ?string $stringClass = null)
    {
        parent::__construct($registry, $stringClass ?? CartPackageItem::class);
    }

}
