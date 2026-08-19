<?php

namespace App\Repository;

use App\Entity\Purchase;
use App\Enum\PurchaseStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Purchase>
 */
class PurchaseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Purchase::class);
    }

    public function getRevenueSince(\DateTimeImmutable $date): string
    {
        $result = $this->createQueryBuilder('p')
            ->select('COALESCE(SUM(p.total), 0)')
            ->andWhere('p.status != :cancelled')
            ->andWhere('p.createdAt >= :date')
            ->setParameter('cancelled', PurchaseStatus::CANCELLED)
            ->setParameter('date', $date)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (string) $result;
    }

    public function countSince(\DateTimeImmutable $date): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.createdAt >= :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    //    /**
    //     * @return Purchase[] Returns an array of Purchase objects
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

    //    public function findOneBySomeField($value): ?Purchase
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
