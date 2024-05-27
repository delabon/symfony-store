<?php

namespace App\Repository;

use App\Entity\Category;
use App\Entity\Product;
use App\Enum\EntityStatusEnum;
use App\Enum\StoreSortEnum;
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

    private function prepareBasicPaginationQuery(int $page, int $limit, ?StoreSortEnum $sortEnum = null): QueryBuilder
    {
        $builder = $this->createQueryBuilder('p');

        if ($sortEnum) {
            if ($sortEnum === StoreSortEnum::NEWEST){
                $builder->orderBy('p.id', 'DESC');
            } else if ($sortEnum === StoreSortEnum::OLDEST) {
                $builder->orderBy('p.id', 'ASC');
            } else if ($sortEnum === StoreSortEnum::PRICE_ASC) {
                $builder->orderBy('p.salePrice', 'ASC');
            } else if ($sortEnum === StoreSortEnum::PRICE_DESC) {
                $builder->orderBy('p.salePrice', 'DESC');
            } else if ($sortEnum === StoreSortEnum::TITLE_ASC) {
                $builder->orderBy('p.name', 'ASC');
            } else if ($sortEnum === StoreSortEnum::TITLE_DESC) {
                $builder->orderBy('p.name', 'DESC');
            }
        } else {
            $builder->orderBy('p.id', 'DESC');
        }

        $builder->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        return $builder;
    }
    public function paginate(int $page, int $limit, ?StoreSortEnum $sortEnum = null): Paginator
    {
        return new Paginator(
            $this->prepareBasicPaginationQuery($page, $limit, $sortEnum)->getQuery(),
            fetchJoinCollection: false
        );
    }

    public function paginatePublished(int $page, int $limit, ?StoreSortEnum $sortEnum = null): Paginator
    {
        return new Paginator(
            $this->prepareBasicPaginationQuery($page, $limit, $sortEnum)
                ->where('p.status = :status')
                ->setParameter('status', EntityStatusEnum::PUBLISHED->value)
                ->getQuery(),
            fetchJoinCollection: false
        );
    }

    public function paginatePublishedByCategory(Category $category, int $page, int $limit, ?StoreSortEnum $sortEnum = null): Paginator
    {
        return new Paginator(
            $this->prepareBasicPaginationQuery($page, $limit, $sortEnum)
                ->innerJoin('p.category', 'c', 'WITH', 'c.id = :category')
                ->setParameter('category', $category->getId())
                ->where('p.status = :status')
                ->setParameter('status', EntityStatusEnum::PUBLISHED->value)
                ->getQuery(),
            fetchJoinCollection: true
        );
    }

    public function paginatePublishedBySearch(string $search, int $page, int $limit, ?StoreSortEnum $sortEnum = null): Paginator
    {
        return new Paginator(
            $this->prepareBasicPaginationQuery($page, $limit, $sortEnum)
                ->where('p.name LIKE :search')
                ->setParameter('search', '%' . $search . '%')
                ->andWhere('p.status = :status')
                ->setParameter('status', EntityStatusEnum::PUBLISHED->value)
                ->getQuery(),
            fetchJoinCollection: false
        );
    }
}
