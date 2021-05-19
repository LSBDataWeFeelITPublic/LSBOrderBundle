<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Repository;

use Doctrine\Persistence\ManagerRegistry;
use LSB\OrderBundle\Entity\OrderNote;
use LSB\UtilityBundle\Repository\BaseRepository;
use LSB\UtilityBundle\Repository\PaginationInterface;
use LSB\UtilityBundle\Repository\PaginationRepositoryTrait;

/**
 * Class OrderNoteRepository
 * @package LSB\OrderBundle\Repository
 */
class OrderNoteRepository extends BaseRepository implements OrderNoteRepositoryInterface, PaginationInterface
{
    use PaginationRepositoryTrait;

    /**
     * OrderNoteRepository constructor.
     * @param ManagerRegistry $registry
     * @param string|null $stringClass
     */
    public function __construct(ManagerRegistry $registry, ?string $stringClass = null)
    {
        parent::__construct($registry, $stringClass ?? OrderNote::class);
    }

}
