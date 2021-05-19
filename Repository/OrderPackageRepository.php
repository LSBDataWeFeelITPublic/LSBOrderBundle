<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Repository;

use Doctrine\Persistence\ManagerRegistry;
use LSB\OrderBundle\Entity\OrderPackage;
use LSB\UtilityBundle\Repository\BaseRepository;
use LSB\UtilityBundle\Repository\PaginationInterface;
use LSB\UtilityBundle\Repository\PaginationRepositoryTrait;

/**
 * Class OrderPackageRepository
 * @package LSB\OrderBundle\Repository
 */
class OrderPackageRepository extends BaseRepository implements OrderPackageRepositoryInterface, PaginationInterface
{
    use PaginationRepositoryTrait;

    /**
     * OrderPackageRepository constructor.
     * @param ManagerRegistry $registry
     * @param string|null $stringClass
     */
    public function __construct(ManagerRegistry $registry, ?string $stringClass = null)
    {
        parent::__construct($registry, $stringClass ?? OrderPackage::class);
    }

}
