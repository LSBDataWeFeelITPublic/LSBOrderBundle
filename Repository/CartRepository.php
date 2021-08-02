<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Repository;

use Doctrine\Persistence\ManagerRegistry;
use LSB\OrderBundle\Entity\Cart;
use LSB\OrderBundle\Entity\CartInterface;
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


    /**
     * @param string $uuid
     * @return CartInterface|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getAbandonedCart(string $uuid): ?CartInterface
    {
        $dateFrom = new \DateTime('now');
        $dateFrom->sub(new \DateInterval('P60D'));

        return $this->createQueryBuilder('c')
            ->where('c.validatedStep < :orderCreatedStep OR c.validatedStep IS NULL')
            ->andWhere('c.abandonmentToken IS NOT NULL')
            ->andWhere('c.uuid = :uuid')
            ->andWhere('c.updatedAt >= :dateFrom')
            ->setParameter('uuid', $uuid)
            ->setParameter('orderCreatedStep', CartInterface::CART_STEP_ORDER_CREATED)
            ->setParameter('dateFrom', $dateFrom)
            ->getQuery()
            ->getOneOrNullResult();
    }

}
