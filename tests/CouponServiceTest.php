<?php

namespace App\Tests;

use App\Entity\Coupon;
use App\Enum\CouponType;
use App\Repository\CouponRepository;
use App\Service\CartService;
use App\Service\CouponService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class CouponServiceTest extends TestCase
{
    private CouponRepository&MockObject $couponRepository;
    private CartService&MockObject $cartService;
    private CouponService $couponService;

    protected function setUp(): void
    {
        $this->couponRepository = $this->createMock(CouponRepository::class);
        $this->cartService = $this->createMock(CartService::class);

        $requestStack = new RequestStack();
        $session = new Session(new MockArraySessionStorage());
        $requestStack->push(new \Symfony\Component\HttpFoundation\Request());
        $requestStack->getCurrentRequest()->setSession($session);

        $this->couponService = new CouponService($this->couponRepository, $requestStack, $this->cartService);
    }

    public function testCalculateDiscountPercentage(): void
    {
        $coupon = $this->makeCoupon(CouponType::PERCENTAGE, '10');
        $discount = $this->couponService->calculateDiscount('100.00', $coupon);
        $this->assertSame('10.00', $discount);
    }

    public function testCalculateDiscountFixed(): void
    {
        $coupon = $this->makeCoupon(CouponType::FIXED, '15.00');
        $discount = $this->couponService->calculateDiscount('100.00', $coupon);
        $this->assertSame('15.00', $discount);
    }

    public function testDiscountNeverExceedsCartTotal(): void
    {
        $coupon = $this->makeCoupon(CouponType::FIXED, '50.00');
        $discount = $this->couponService->calculateDiscount('20.00', $coupon);
        $this->assertSame('20.00', $discount);
    }

    public function testApplyThrowsOnUnknownCode(): void
    {
        $this->couponRepository->method('findOneBy')->willReturn(null);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Code promo invalide');
        $this->couponService->apply('INCONNU');
    }

    public function testApplyThrowsOnExpiredCoupon(): void
    {
        $coupon = $this->makeCoupon(CouponType::PERCENTAGE, '10', expiresAt: new \DateTimeImmutable('-1 day'));
        $this->couponRepository->method('findOneBy')->willReturn($coupon);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('expiré');
        $this->couponService->apply('TEST1');
    }

    public function testApplyThrowsWhenUsageLimitReached(): void
    {
        $coupon = $this->makeCoupon(CouponType::PERCENTAGE, '10', usageLimit: 5, usageCount: 5);
        $this->couponRepository->method('findOneBy')->willReturn($coupon);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('limite');
        $this->couponService->apply('TEST1');
    }

    public function testApplyThrowsWhenBelowMinAmount(): void
    {
        $coupon = $this->makeCoupon(CouponType::PERCENTAGE, '10', minAmount: '50.00');
        $this->couponRepository->method('findOneBy')->willReturn($coupon);
        $this->cartService->method('getTotal')->willReturn('30.00');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('minimum');
        $this->couponService->apply('TEST1');
    }

    public function testApplySucceedsAndStoresCodeInSession(): void
    {
        $coupon = $this->makeCoupon(CouponType::PERCENTAGE, '10', minAmount: '10.00');
        $this->couponRepository->method('findOneBy')->willReturn($coupon);
        $this->cartService->method('getTotal')->willReturn('50.00');
        $result = $this->couponService->apply('test1');
        $this->assertSame('TEST1', $result->getCode());
        $this->assertSame(CouponType::PERCENTAGE, $result->getType());
        
    }

    private function makeCoupon(
        CouponType $type,
        string $value,
        ?string $minAmount = null,
        ?\DateTimeImmutable $expiresAt = null,
        ?int $usageLimit = null,
        int $usageCount = 0
    ): Coupon {
        $coupon = new Coupon();
        $coupon->setCode('TEST1');
        $coupon->setType($type);
        $coupon->setValue($value);
        $coupon->setMinAmount($minAmount);
        $coupon->setExpiresAt($expiresAt);
        $coupon->setUsageLimit($usageLimit);

        for ($i = 0; $i < $usageCount; $i++) {
            $coupon->incrementUsage();
        }
        
        return $coupon;
    }
}
