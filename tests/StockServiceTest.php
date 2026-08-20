<?php

namespace App\Tests;

use App\Entity\ProductVariant;
use App\Entity\Purchase;
use App\Entity\Stock;
use App\Entity\StockMovement;
use App\Enum\StockMovementType;
use App\Repository\StockRepository;
use App\Service\StockService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StockServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private StockRepository&MockObject $stockRepository;
    private StockService $stockService;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->stockRepository = $this->createMock(StockRepository::class);
        $this->stockService = new StockService($this->entityManager, $this->stockRepository);
    }

    public function testRestockIncreasesQuantityAndPersistsEntreeMovement(): void
    {
        $stock = $this->makeStock(10);
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (StockMovement $movement) {
                return $movement->getType() === StockMovementType::ENTRY
                    && $movement->getQty() === 5
                    && $movement->getReason() === 'Réassort';
            }));
        
        $this->stockService->restock($stock, 5, 'Réassort');
        $this->assertSame(15, $stock->getQty());
    }

    public function testReserveForSaleDecreasesQuantityAndLinksPurchase(): void
    {
        $stock = $this->makeStock(10);
        $purchase = new Purchase();
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (StockMovement $movement) use ($purchase) {
                return $movement->getType() === StockMovementType::SALE
                    && $movement->getQty() === 3
                    && $movement->getPurchase() === $purchase;
            }));
        
        $this->stockService->reserveForSale($stock, 3, $purchase);
        $this->assertSame(7, $stock->getQty());
    }

    public function testReserveForSaleThrowsWhenInsufficientStock(): void
    {
        $variant = new ProductVariant();
        $variant->setSku('SKU-1');
        $variant->setPrice('10.00');

        $product = new \App\Entity\Product();
        $product->setName('T-shirt');
        $product->setSlug('t-shirt');
        $variant->setProduct($product);

        $stock = $this->makeStock(2);
        $stock->setVariant($variant);
        
        $purchase = new Purchase();
        $this->entityManager->expects($this->never())->method('persist');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Stock insuffisant');
        $this->stockService->reserveForSale($stock, 5, $purchase);
        $this->assertSame(2, $stock->getQty());
    }

    public function testReturnStockIncreasesQuantityWithReturnType(): void
    {
        $stock = $this->makeStock(4);
        $purchase = new Purchase();

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(fn (StockMovement $m) => $m->getType() === StockMovementType::BACK));
        $this->stockService->returnStock($stock, 2, $purchase, 'Retour client');
        $this->assertSame(6, $stock->getQty());
    }

    public function testAdjustWithPositiveDelatCreatesEntryMovement(): void
    {
        $stock = $this->makeStock(10);
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(fn (StockMovement $m) => $m->getType() === StockMovementType::ENTRY && $m->getQty() === 3));
        $this->stockService->adjust($stock, 3, 'Inventaire');
        $this->assertSame(13, $stock->getQty());
    }

    public function testAdjustWithNegativeDelatCreatesOutingMovement(): void
    {
        $stock = $this->makeStock(10);
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(fn (StockMovement $m) => $m->getType() === StockMovementType::OUTING && $m->getQty() === 4));
        $this->stockService->adjust($stock, -4, 'Casse');
        $this->assertSame(6, $stock->getQty());
    }

    public function testInitializeWithoutInitialQuantityDoesNotCreateMovement(): void
    {
        $variant = new ProductVariant();
        $variant->setSku('SKU-2');
        $variant->setPrice('15.00');
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Stock::class));
        $stock = $this->stockService->initialize($variant);
        $this->assertSame(0, $stock->getQty());
    }

    public function testInitializeWithInitialQuantityCreatesEntryMovement(): void
    {
        $variant = new ProductVariant();
        $variant->setSku('SKU-3');
        $variant->setPrice('15.00');
        $persisted = [];
        $this->entityManager->method('persist')->willReturnCallback(function ($entity) use (&$persisted) {
            $persisted[] = $entity;
        });
        $stock = $this->stockService->initialize($variant, 20);
        $this->assertCount(2, $persisted);
        $this->assertInstanceOf(StockMovement::class, $persisted[1]);
        $this->assertSame(StockMovementType::ENTRY, $persisted[1]->getType());
        $this->assertSame(20, $stock->getQty());
    }

    private function makeStock(int $qty): Stock
    {
        $stock = new Stock();
        $stock->setQty($qty);

        return $stock;
    }
}
