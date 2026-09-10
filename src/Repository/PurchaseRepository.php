<?php

namespace App\Repository;

use App\Entity\Purchase;
use App\Enum\PurchaseStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Accès aux données des commandes.
 *
 * @package App\Repository
 * @extends ServiceEntityRepository<Purchase>
 */
class PurchaseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Purchase::class);
    }

    /**
     * Chiffre d'affaires cumulé depuis une date donnée
     * (commandes non annulées uniquement).
     *
     * @param \DateTimeImmutable $date
     * @return numeric-string
     */
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

        if (!is_numeric($result)) {
            throw new \UnexpectedValueException('Le chiffre d’affaires retourné n’est pas numérique.');
        }

        return (string) $result;
    }

    /**
     * Nombre de commandes passées depuis une date donnée.
     *
     * @param \DateTimeImmutable $date
     * @return integer
     */
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
}
