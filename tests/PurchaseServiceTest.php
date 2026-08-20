<?php

namespace App\Tests;

use App\Entity\User;
use App\Entity\Stock;
use App\Entity\Coupon;
use App\Enum\CouponType;
use App\Service\CartService;
use App\Service\StockService;
use App\Entity\ProductVariant;
use App\Service\CouponService;
use PHPUnit\Framework\TestCase;
use App\Service\PurchaseService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Workflow\WorkflowInterface;

class PurchaseServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private CartService&MockObject $cartService;
    private CouponService&MockObject $couponService;
    private StockService&MockObject $stockService;
    private WorkflowInterface&MockObject $workflow;
    private PurchaseService $purchaseService;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->cartService = $this->createMock(CartService::class);
        $this->couponService = $this->createMock(CouponService::class);
        $this->stockService = $this->createMock(StockService::class);
        $this->workflow = $this->createMock(WorkflowInterface::class);

        $this->purchaseService = new PurchaseService(
            $this->entityManager,
            $this->cartService,
            $this->couponService,
            $this->stockService,
            $this->workflow,
        );
    }

    public function testCreateFromCartThrowsWhenCartIsEmpty(): void
    {
        $this->cartService->method('isEmpty')->willReturn(true);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('panier est vide');

        $this->purchaseService->createFromCart(new User(), '1 rue de Paris');
    }

    public function testCreateFromCartThrowsWhenStockInsufficient(): void
    {
        $variant = $this->makeVariant('20.00');
        $stock = new Stock();
        $stock->setQty(1);

        $this->cartService->method('isEmpty')->willReturn(false);
        $this->cartService->method('getItems')->willReturn([
            ['variant' => $variant, 'qty' => 3, 'subtotal' => '60.00'],
        ]);
        $this->stockService->method('getStockForVariant')->willReturn($stock);

        $this->entityManager->expects($this->never())->method('persist');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Stock insuffisant');

        $this->purchaseService->createFromCart(new User(), '1 rue de Paris');
    }

    public function testCreateFromCartThrowsWhenNoStockRecordExists(): void
    {
        $variant = $this->makeVariant('20.00');

        $this->cartService->method('isEmpty')->willReturn(false);
        $this->cartService->method('getItems')->willReturn([
            ['variant' => $variant, 'qty' => 1, 'subtotal' => '20.00'],
        ]);
        $this->stockService->method('getStockForVariant')->willReturn(null);

        $this->expectException(\RuntimeException::class);

        $this->purchaseService->createFromCart(new User(), '1 rue de Paris');
    }

    public function testCreateFromCartAppliesCouponDiscountToTotal(): void
    {
        $variant = $this->makeVariant('50.00');
        $stock = new Stock();
        $stock->setQty(10);

        $coupon = new Coupon();
        $coupon->setCode('PROMO10');
        $coupon->setType(CouponType::PERCENTAGE);
        $coupon->setValue('10');

        $this->cartService->method('isEmpty')->willReturn(false);
        $this->cartService->method('getItems')->willReturn([
            ['variant' => $variant, 'qty' => 2, 'subtotal' => '100.00'],
        ]);
        $this->cartService->method('getTotal')->willReturn('100.00');
        $this->stockService->method('getStockForVariant')->willReturn($stock);
        $this->couponService->method('getApplied')->willReturn($coupon);
        $this->couponService->method('calculateDiscount')->willReturn('10.00');

        $captured = null;
        $this->entityManager->method('persist')->willReturnCallback(function ($entity) use (&$captured) {
            if ($entity instanceof \App\Entity\Purchase) {
                $captured = $entity;
            }
        });

        $purchase = $this->purchaseService->createFromCart(new User(), '1 rue de Paris');

        $this->assertSame('90.00', $purchase->getTotal());
        $this->assertSame('10.00', $purchase->getDiscount());
        $this->assertSame($coupon, $purchase->getCoupon());
    }

    public function testCreateFromCartClearsCartAfterSuccess(): void
    {
        $variant = $this->makeVariant('20.00');
        $stock = new Stock();
        $stock->setQty(5);

        $this->cartService->method('isEmpty')->willReturn(false);
        $this->cartService->method('getItems')->willReturn([
            ['variant' => $variant, 'qty' => 1, 'subtotal' => '20.00'],
        ]);
        $this->cartService->method('getTotal')->willReturn('20.00');
        $this->stockService->method('getStockForVariant')->willReturn($stock);
        $this->couponService->method('getApplied')->willReturn(null);
        $this->couponService->method('calculateDiscount')->willReturn('0.00');

        $this->cartService->expects($this->once())->method('clear');

        $this->purchaseService->createFromCart(new User(), '1 rue de Paris');
    }

    private function makeVariant(string $price): ProductVariant
    {
        $variant = new ProductVariant();
        $variant->setSku('SKU-1');
        $variant->setPrice($price);

        $product = new \App\Entity\Product();
        $product->setName('T-shirt');
        $product->setSlug('t-shirt');
        $variant->setProduct($product);

        return $variant;
    }
}
