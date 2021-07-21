<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Repository;

use Doctrine\Persistence\ManagerRegistry;
use LSB\OrderBundle\Entity\CartPackage;
use LSB\UtilityBundle\Repository\BaseRepository;
use LSB\UtilityBundle\Repository\PaginationRepositoryTrait;

/**
 * Class CartPackageRepository
 * @package LSB\OrderBundle\Repository
 */
class CartPackageRepository extends BaseRepository implements CartPackageRepositoryInterface
{
    use PaginationRepositoryTrait;

    /**
     * CartPackageRepository constructor.
     * @param ManagerRegistry $registry
     * @param string|null $stringClass
     */
    public function __construct(ManagerRegistry $registry, ?string $stringClass = null)
    {
        parent::__construct($registry, $stringClass ?? CartPackage::class);
    }

}
