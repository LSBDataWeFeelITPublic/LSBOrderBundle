<?php
declare(strict_types=1);

namespace LSB\OrderBundle\Repository;

use Doctrine\Persistence\ManagerRegistry;
use LSB\OrderBundle\Entity\CartItem;
use LSB\OrderBundle\Model\CartItemModule\CartItemRequestProductData;
use LSB\OrderBundle\Model\CartItemModule\CartItemRequestProductDataCollection;
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

    /**
     * @param int $cartId
     * @param CartItemRequestProductDataCollection $updateData
     * @return array
     */
    public function getCartItemsByCartProductAndOrderCodes(int $cartId, CartItemRequestProductDataCollection $updateData): array
    {
        $qb = $this->createQueryBuilder('ci')
            ->select('ci')
            ->leftJoin('ci.product', 'p')
            ->where('ci.cart = :cartId')
            ->setParameter('cartId', $cartId);
        $orX = $qb->expr()->orX();


        /**
         * @var CartItemRequestProductData $row
         */
        foreach ($updateData->getFlatCollection() as $key => $row) {
            if ($row->getOrderCode()) {
                $orX->add("p.uuid = :product{$key} AND ci.orderCode = :orderCode{$key} ");
                $qb->setParameter("orderCode{$key}", $row->getOrderCode());
            } else {
                $orX->add("p.uuid = :product{$key}");
            }
            $qb->setParameter("product{$key}", $row->getProductUuid());
        }

        $qb->andWhere($orX);

        return $qb->getQuery()->execute();
    }

}
