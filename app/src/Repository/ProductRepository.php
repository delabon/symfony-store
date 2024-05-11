<?php

namespace App\Repository;

use App\Entity\Category;
use App\Entity\Product;
use App\Enum\ProductStatusEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 *
 * @method Product|null find($id, $lockMode = null, $lockVersion = null)
 * @method Product|null findOneBy(array $criteria, array $orderBy = null)
 * @method Product[]    findAll()
 * @method Product[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    private function prepareBasicPaginationQuery(int $page, int $limit): QueryBuilder
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.id', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);
    }
    public function paginate(int $page, int $limit): Paginator
    {
        return new Paginator(
            $this->prepareBasicPaginationQuery($page, $limit)->getQuery(),
            fetchJoinCollection: false
        );
    }

    public function paginatePublished(int $page, int $limit): Paginator
    {
        return new Paginator(
            $this->prepareBasicPaginationQuery($page, $limit)
                ->where('p.status = :status')
                ->setParameter('status', ProductStatusEnum::PUBLISHED->value)
                ->andWhere('p.quantity != 0')
                ->getQuery(),
            fetchJoinCollection: false
        );
    }

    public function paginatePublishedByCategory(Category $category, int $page, int $limit): Paginator
    {
        return new Paginator(
            $this->prepareBasicPaginationQuery($page, $limit)
                ->innerJoin('p.category', 'c', 'WITH', 'c.id = :category')
                ->setParameter('category', $category->getId())
                ->where('p.status = :status')
                ->setParameter('status', ProductStatusEnum::PUBLISHED->value)
                ->andWhere('p.quantity != 0')
                ->getQuery(),
            fetchJoinCollection: false
        );
    }

    public function paginatePublishedBySearch(string $search, int $page, int $limit): Paginator
    {
        return new Paginator(
            $this->prepareBasicPaginationQuery($page, $limit)
                ->where('p.name LIKE :search')
                ->setParameter('search', '%' . $search . '%')
                ->andWhere('p.status = :status')
                ->setParameter('status', ProductStatusEnum::PUBLISHED->value)
                ->andWhere('p.quantity != 0')
                ->getQuery(),
            fetchJoinCollection: false
        );
    }
}
