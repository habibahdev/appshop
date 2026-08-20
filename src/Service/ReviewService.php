<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Review;
use App\Entity\Product;
use App\Enum\PurchaseStatus;
use App\Repository\ReviewRepository;
use App\Repository\PurchaseRepository;
use Doctrine\ORM\EntityManagerInterface;

class ReviewService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ReviewRepository $reviewRepository,
        private PurchaseRepository $purchaseRepository
    ) {
    }

    public function create(Product $product, User $user, int $rating, string $comment): Review
    {
        if ($this->reviewRepository->hasUserReviewed($product, $user)) {
            throw new \InvalidArgumentException('Tu as déjà laissé un avis sur ce produit');
        }

        $review = new Review();
        $review->setProduct($product);
        $review->setUser($user);
        $review->setRating($rating);
        $review->setComment($comment);
        $review->setVerifiedPurchase($this->hasPurchased($product, $user));
        $this->entityManager->persist($review);
        $this->entityManager->flush();

        return $review;
    }

    private function hasPurchased(Product $product, User $user): bool
    {
        return $this->purchaseRepository->createQueryBuilder('p')
            ->innerJoin('p.details', 'd')
            ->innerJoin('d.variant', 'v')
            ->andWhere('p.user = :user')
            ->andWhere('v.product = :product')
            ->andWhere('p.status IN (:paidStatuses)')
            ->setParameter('user', $user)
            ->setParameter('product', $product)
            ->setParameter('paidStatuses', [
                PurchaseStatus::PAID,
                PurchaseStatus::PREPARATION,
                PurchaseStatus::SHIPPED,
                PurchaseStatus::DELIVERED,
            ])
            ->getQuery()
            ->getOneOrNullResult() !== null
        ;
    }
}
