<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Repository;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
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


    /**
     * @throws Exception
     */
    public function getCartForUser(
        ?int    $userId,
        ?int    $customerId,
        ?string $sessionId,
        ?int    $cartValidDays,
        ?int    $type = null
    ): ?Cart {
        $cartValidDate = new \DateTime('Now');

        //Change of cart validity
        if ($cartValidDays) {
            $cartValidDate->sub(new \DateInterval('P' . $cartValidDays . 'D'));
        } else {
            $cartValidDate = null;
        }

        $qb = $this->createQueryBuilder('c')
            ->select('c')
            ->leftJoin('c.shopUser', 'u')
            ->andWhere('c.validatedStep IS NULL OR c.validatedStep < :finalStep')
            ->setParameter('finalStep', CartInterface::CART_STEP_ORDER_CREATED);

        if ($type) {
            $qb
                ->andWhere('c.type = :type')
                ->setParameter('type', $type);
        }


        if ($cartValidDate) {
            $qb->andWhere('c.updatedAt >= :cartValidDate')
                ->setParameter('cartValidDate', $cartValidDate, Types::DATETIME_MUTABLE);
        }

        if ($userId && $customerId) {
            //Session ID is not taken into account when we have userId and customerId
            $qb->andWhere('c.shopUser = :userId')
                ->andWhere('c.customer = :customerId')
                ->setParameter('customerId', $customerId)
                ->setParameter('userId', $userId);

        } elseif ($sessionId) {
            $qb->andWhere('c.sessionId = :sessionId')
                ->andWhere('c.shopUser IS NULL or (u.isHiddenUser = TRUE OR u.enabled = FALSE)')
                ->andWhere('c.customer IS NULL or (u.isHiddenUser = TRUE OR u.enabled = FALSE)')
                ->setParameter('sessionId', $sessionId);
        } else {
            throw new Exception('UserId and customerId or sessionId must be passed and cannot be null.');
        }

        $qb
            ->orderBy('c.id', 'DESC')
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function getByTransactionId(string $transactionId): ?CartInterface
    {
        $dateFrom = new \DateTime('now');
        $dateFrom->sub(new \DateInterval('P60D'));

        $qb = $this->createQueryBuilder('c')
            ->where('c.transactionId = :transactionId')
            ->setParameter('transactionId', $transactionId)
            ->orderBy('c.id', 'DESC')
            ->setMaxResults(1)
        ;

        $paginator = new Paginator($qb->getQuery());

        /** @var CartInterface $cart */
        foreach ($paginator as $cart) {
            return $cart;
        }

        return null;
    }

}
