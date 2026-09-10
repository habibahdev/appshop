<?php

namespace App\Repository;

use App\Entity\ProductVariant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Accès aux données des variantes de produits.
 *
 * @package App\Repository
 * @extends ServiceEntityRepository<ProductVariant>
 */
class ProductVariantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductVariant::class);
    }

    /**
     * @return ProductVariant[]
     */
    public function findLowStock(): array
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.stock > 0')
            ->andWhere('v.stock <= v.stockAlertThreshold')
            ->andWhere('v.isActive = true')
            ->getQuery()
            ->getResult()
        ;
    }
}
