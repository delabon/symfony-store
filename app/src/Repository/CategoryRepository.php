<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 *
 * @method Category|null find($id, $lockMode = null, $lockVersion = null)
 * @method Category|null findOneBy(array $criteria, array $orderBy = null)
 * @method Category[]    findAll()
 * @method Category[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    public function paginate(int $page, int $limit): Paginator
    {
        return new Paginator(
            $this->createQueryBuilder('c')
                ->orderBy('c.id', 'DESC')
                ->setFirstResult(($page - 1) * $limit)
                ->setMaxResults($limit)
                ->getQuery(),
            fetchJoinCollection: false
        );
    }

    public function nonEmptyCategories(): array
    {
        return $this->createQueryBuilder('c')
            ->select('c.id', 'c.name', 'c.slug', 'COUNT(p.id) as productsCount')
            ->leftJoin('c.products', 'p')
            ->groupBy('c.id')
            ->having('productsCount > 0')
            ->orderBy('productsCount', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
