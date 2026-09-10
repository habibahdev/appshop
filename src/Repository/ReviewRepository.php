<?php

namespace App\Repository;

use App\Entity\Product;
use App\Entity\Review;
use App\Entity\User;
use App\Enum\ReviewStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Accès aux données des avis produit.
 *
 * @package App\Repository
 * @extends ServiceEntityRepository<Review>
 */
class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
    }

    /**
     * @param Product $product Produit concerné.
     * @return float|null Note moyenne des avis approuvés, null si aucun avis.
     */
    public function getAverageRating(Product $product): ?float
    {
        $result = $this->createQueryBuilder('r')
            ->select('AVG(r.rating) as avg')
            ->andWhere('r.product = :product')
            ->andWhere('r.status = :status')
            ->setParameter('product', $product)
            ->setParameter('status', ReviewStatus::APPROVED)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return $result !== null ? round((float) $result, 1) : null;
    }

    /**
     * @param Product $product Produit concerné.
     * @return Review[] Avis approouvés pour ce produit, triés du plus récent au plus ancien.
     */
    public function findApprovedForProduct(Product $product): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.product = :product')
            ->andWhere('r.status = :status')
            ->setParameter('product', $product)
            ->setParameter('status', ReviewStatus::APPROVED)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @param Product $product Produit concerné.
     * @param User $user Utilisateur concerné.
     * @return boolean True si cet utilisateur a déjà un avis sur ce produit.
     */
    public function hasUserReviewed(Product $product, User $user): bool
    {
        return (bool) $this->count([
            'product' => $product,
            'user' => $user
        ]);
    }
}
