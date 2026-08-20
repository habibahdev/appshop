<?php

namespace App\Tests;

use App\Entity\User;
use App\Entity\Review;
use App\Entity\Product;
use Doctrine\ORM\Query;
use App\Service\ReviewService;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use App\Repository\ReviewRepository;
use App\Repository\PurchaseRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;

class ReviewServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private ReviewRepository&MockObject $reviewRepository;
    private PurchaseRepository&MockObject $purchaseRepository;
    private ReviewService $reviewService;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->reviewRepository = $this->createMock(ReviewRepository::class);
        $this->purchaseRepository = $this->createMock(PurchaseRepository::class);
        $this->reviewService = new ReviewService($this->entityManager, $this->reviewRepository, $this->purchaseRepository);
    }

    public function testCreateThrowsIfUserAlreadyReviewedProduct(): void
    {
        $product = new Product();
        $product->setName('T-shirt');
        $product->setSlug('t-shirt');

        $user = new User();
        $user->setEmail('client@example.com');

        $this->reviewRepository->method('hasUserReviewed')->willReturn(true);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('déjà laissé un avis');

        $this->reviewService->create($product, $user, 5, 'Top produit');
    }

    public function testCreateMarksVerifiedPurchaseWhenUserBoughtProduct(): void
    {
        $product = new Product();
        $product->setName('T-shirt');
        $product->setSlug('t-shirt');

        $user = new User();
        $user->setEmail('client@example.com');

        $this->reviewRepository->method('hasUserReviewed')->willReturn(false);
        $this->mockPurchaseQueryBuilder(hasPurchased: true);

        $captured = null;
        $this->entityManager->method('persist')->willReturnCallback(function ($entity) use (&$captured) {
            $captured = $entity;
        });

        $review = $this->reviewService->create($product, $user, 4, 'Très bien');

        $this->assertInstanceOf(Review::class, $captured);
        $this->assertTrue($review->isVerifiedPurchase());
        $this->assertSame(4, $review->getRating());
    }

    public function testCreateLeavesVerifiedPurchaseFalseWhenNoPurchaseFound(): void
    {
        $product = new Product();
        $product->setName('T-shirt');
        $product->setSlug('t-shirt');

        $user = new User();
        $user->setEmail('client@example.com');

        $this->reviewRepository->method('hasUserReviewed')->willReturn(false);
        $this->mockPurchaseQueryBuilder(hasPurchased: false);

        $review = $this->reviewService->create($product, $user, 3, 'Correct');

        $this->assertFalse($review->isVerifiedPurchase());
    }

    private function mockPurchaseQueryBuilder(bool $hasPurchased): void
    {
        $query = $this->createMock(Query::class);

        $query
            ->method('getOneOrNullResult')
            ->willReturn($hasPurchased ? new \stdClass() : null);

        $qb = $this->createMock(QueryBuilder::class);

        $qb->method('innerJoin')->willReturnSelf();
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);

        $this->purchaseRepository
            ->method('createQueryBuilder')
            ->willReturn($qb);
    }
}
