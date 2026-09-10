<?php

namespace App\Repository;

use App\Entity\ProductVariant;
use App\Entity\Stock;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Accès aux données de stock.
 *
 * @package App\Repository
 * @extends ServiceEntityRepository<Stock>
 */
class StockRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Stock::class);
    }

    /**
     * @return Stock[] Enregistrements de stock sous le seuil d'alerte
     * mais non épuisés (0 < qty <= alertThreshold).
     */
    public function findLowStock(): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.qty > 0')
            ->andWhere('s.qty <= s.alertThreshold')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Résout le stock associé à une variante donnée.
     *
     * Point d'accès unique pour la relation unidirectionnelle
     * {@see ProductVariant} -> {@see Stock} - à utiliser partout où
     * le stock d'une variante doit être consulté, {@see ProductVariant}
     * n'exposant aucun accesseur direct.
     *
     * @param ProductVariant $variant Variante recherchée.
     * @return Stock|null Le stock associé, ou null si non initialisé.
     */
    public function findOneByVariant(ProductVariant $variant): ?Stock
    {
        return $this->findOneBy(['variant' => $variant]);
    }
}
