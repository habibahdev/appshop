<?php

namespace App\Tests;

use App\Entity\ProductVariant;
use App\Entity\Stock;
use App\Repository\ProductVariantRepository;
use App\Repository\StockRepository;
use App\Service\CartService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class CartServiceTest extends TestCase
{
    private ProductVariantRepository&MockObject $variantRepository;
    private StockRepository&MockObject $stockRepository;
    private CartService $cartService;

    protected function setUp(): void
    {
        $this->variantRepository = $this->createMock(ProductVariantRepository::class);
        $this->stockRepository = $this->createMock(StockRepository::class);

        $requestStack = new RequestStack();
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        $requestStack->push($request);
        $this->cartService = new CartService($requestStack, $this->variantRepository, $this->stockRepository);
    }

    public function testAddCapsQuantityAtAvailableStock(): void
    {
        $variant = $this->makeVariant(1, '20.00');
        $this->variantRepository->method('find')->with(1)->willReturn($variant);
        $this->stockRepository->method('findOneByVariant')->willReturn($this->makeStock(3));
        $this->cartService->add(1, 10);
        $this->assertSame(3, $this->cartService->getTotalQty());
    }

    public function testAddWithNoStockRecordResultsInZeroQuantity(): void
    {
        $variant = $this->makeVariant(1, '20.00');
        $this->variantRepository->method('find')->with(1)->willReturn($variant);
        $this->stockRepository->method('findOneByVariant')->willReturn(null);
        $this->cartService->add(1, 5);
        $this->assertTrue($this->cartService->isEmpty());
    }

    public function testAddThrowsOnUnknownVariant(): void
    {
        $this->variantRepository->method('find')->with(999)->willReturn(null);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Variant introuvable');
        $this->cartService->add(999, 1);
    }

    public function testUpdateQuantityToZeroRemovesItem(): void
    {
        $variant = $this->makeVariant(1, '20.00');
        $this->variantRepository->method('find')->with(1)->willReturn($variant);
        $this->stockRepository->method('findOneByVariant')->willReturn($this->makeStock(5));
        $this->cartService->add(1, 2);
        $this->cartService->updateQty(1, 0);
        $this->assertTrue($this->cartService->isEmpty());
    }

    public function testUpdateQuantityIsCappedByStock(): void
    {
        $variant = $this->makeVariant(1, '20.00');
        $this->variantRepository->method('find')->with(1)->willReturn($variant);
        $this->stockRepository->method('findOneByVariant')->willReturn($this->makeStock(4));
        $this->cartService->updateQty(1, 100);
        $this->assertSame(4, $this->cartService->getTotalQty());
    }

    public function testGetTotalSumsSubtotalsWithDecimalPrecision(): void
    {
        $variant1 = $this->makeVariant(1, '19.99');
        $variant2 = $this->makeVariant(2, '5.01');
        $this->variantRepository->method('find')->willReturnMap([[1, $variant1], [2, $variant2]]);
        $this->variantRepository->method('findBy')->willReturn([$variant1, $variant2]);
        $this->stockRepository->method('findOneByVariant')->willReturn($this->makeStock(10));
        $this->cartService->add(1, 2);
        $this->cartService->add(2, 1);
        $this->assertSame('44.99', $this->cartService->getTotal());
    }

    private function makeStock(int $qty): Stock
    {
        $stock = new Stock();
        $stock->setQty($qty);

        return $stock;
    }

    private function makeVariant(int $id, string $price): ProductVariant
    {
        $variant = new ProductVariant();
        $variant->setSku('SKU-' . $id);
        $variant->setPrice($price);
        $ref = new \ReflectionProperty($variant, 'id');
        $ref->setAccessible(true);
        $ref->setValue($variant, $id);

        return $variant;
    }
}
