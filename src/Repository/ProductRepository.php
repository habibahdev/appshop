<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    private const PER_PAGE = 12;

    public function __construct(
        ManagerRegistry $registry,
        private TagAwareCacheInterface $cacheCatalog
    ) {
        parent::__construct($registry, Product::class);
    }

    /**
     * @return Product[]
     */
    public function findByFilters(?int $categoryId = null, ?string $search = null, string $sort = 'newest'): array
    {
        if ($search) {
            return $this->doFindByFilters($categoryId, $search, $sort);
        }

        $cacheKey = sprintf('catalog_%s_%s', $categoryId ?? 'all', $sort);

        return $this->cacheCatalog->get($cacheKey, function (ItemInterface $item) use ($categoryId, $sort) {
            $item->expiresAfter(36000);
            $item->tag(['catalog', $categoryId ? 'category_' . $categoryId : 'catalog_all']);

            return $this->doFindByFilters($categoryId, null, $sort);
        });
    }

    /**
     * @return Product[]
     */
    private function doFindByFilters(?int $categoryId, ?string $search, string $sort): array
    {
        $query = $this->createQueryBuilder('p')
            ->andWhere('p.isActive = true')
            ->leftJoin('p.variants', 'v')
            ->addSelect('v')
        ;

        if ($categoryId) {
            $query
                ->andWhere('p.category = :categoryId')
                ->setParameter('categoryId', $categoryId)
            ;
        }

        if ($search) {
            $query
                ->andWhere('p.name LIKE :search')
                ->setParameter('search', '%' . $search . '%')
            ;
        }

        match ($sort) {
            'price_asc' => $query->orderBy('v.price', 'ASC'),
            'price_desc' => $query->orderBy('v.price', 'DESC'),
            'name' => $query->orderBy('p.name', 'ASC'),
            default => $query->orderBy('p.createdAt', 'DESC')
        };

        return $query
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return array{items: Product[], total: int, page: int, pages: int}
     */
    public function findByFiltersPaginated(
        ?int $categoryId = null,
        ?string $search = null,
        string $sort = 'newest',
        int $page = 1
    ): array {
        $page = max(1, $page);

        if ($search) {
            return $this->doFindByFiltersPaginated($categoryId, $search, $sort, $page);
        }

        $cacheKey = sprintf('catalog_%s_%s_page%d', $categoryId ?? 'all', $sort, $page);

        /** @var array{items: Product[], total: int, page: int, pages: int} */
        return $this->cacheCatalog->get($cacheKey, function (ItemInterface $item) use ($categoryId, $sort, $page) {
            $item->expiresAfter(3600);
            $item->tag(['catalog', $categoryId ? 'category' . $categoryId : 'catalog_all']);

            return $this->doFindByFiltersPaginated($categoryId, null, $sort, $page);
        });
    }

    /**
     * @return array{items: Product[], total: int, page: int, pages: int}
     */
    private function doFindByFiltersPaginated(?int $categoryId, ?string $search, string $sort, int $page): array
    {
        $query = $this->createQueryBuilder('p')
            ->andWhere('p.isActive = true')
            ->leftJoin('p.variants', 'v')
            ->addSelect('v')
        ;

        if ($categoryId) {
            $query
                ->andWhere('p.category = :categoryId')
                ->setParameter('categoryId', $categoryId)
            ;
        }

        if ($search) {
            $query
                ->andWhere('p.name LIKE :search')
                ->setParameter('search', '%' . $search . '%')
            ;
        }

        match ($sort) {
            'price_asc' => $query->orderBy('v.price', 'ASC'),
            'price_desc' => $query->orderBy('v.price', 'DESC'),
            'name' => $query->orderBy('p.name', 'ASC'),
            default => $query->orderBy('p.createdAt', 'DESC')
        };

        $query
            ->setFirstResult((($page - 1)) * self::PER_PAGE)
            ->setMaxResults(self::PER_PAGE)
        ;

        $paginator = new Paginator($query->getQuery(), fetchJoinCollection: true);
        $total = count($paginator);

        return [
            'items' => iterator_to_array($paginator),
            'total' => $total,
            'page' => $page,
            'pages' => (int) max(1, ceil($total / self::PER_PAGE))
        ];
    }

//    /**
//     * @return Product[] Returns an array of Product objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Product
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
