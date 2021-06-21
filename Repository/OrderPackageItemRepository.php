<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Repository;

use Doctrine\Persistence\ManagerRegistry;
use LSB\OrderBundle\Entity\OrderPackageItem;
use LSB\UtilityBundle\Repository\BaseRepository;
use LSB\UtilityBundle\Repository\PaginationRepositoryTrait;

/**
 * Class OrderPackageItemRepository
 * @package LSB\OrderBundle\Repository
 */
class OrderPackageItemRepository extends BaseRepository implements OrderPackageItemRepositoryInterface
{
    use PaginationRepositoryTrait;

    /**
     * OrderPackageItemRepository constructor.
     * @param ManagerRegistry $registry
     * @param string|null $stringClass
     */
    public function __construct(ManagerRegistry $registry, ?string $stringClass = null)
    {
        parent::__construct($registry, $stringClass ?? OrderPackageItem::class);
    }

}
