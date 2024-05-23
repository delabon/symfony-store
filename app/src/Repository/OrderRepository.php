<?php

namespace App\Repository;

use App\Entity\Order;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use InvalidArgumentException;

/**
 * @extends ServiceEntityRepository<Order>
 *
 * @method Order|null find($id, $lockMode = null, $lockVersion = null)
 * @method Order|null findOneBy(array $criteria, array $orderBy = null)
 * @method Order[]    findAll()
 * @method Order[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    public function paginate(int $page, int $limit): Paginator
    {
        return new Paginator(
            $this->createQueryBuilder('o')
                ->orderBy('o.id', 'DESC')
                ->setFirstResult(($page - 1) * $limit)
                ->setMaxResults($limit)
        );
    }

    /**
     * @param int $page
     * @param int $limit
     * @param User $customer
     * @return Paginator
     */
    public function paginateByCustomer(int $page, int $limit, User $customer): Paginator
    {
        if (!$customer->getId()) {
            throw new InvalidArgumentException('The customer is invalid.');
        }

        $builder = $this->createQueryBuilder('o')
            ->orderBy('o.id', 'DESC');

        $builder->where('o.customer = :customer')
            ->setParameter('customer', $customer);

        $builder->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        return new Paginator($builder);
    }

    public function save(Order $order): Order
    {
        if (!$order->getId()) {
            $this->getEntityManager()->persist($order);
        }

        $this->getEntityManager()->flush();

        return $order;
    }
}
