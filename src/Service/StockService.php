<?php

namespace App\Service;

use App\Entity\ProductVariant;
use App\Entity\Purchase;
use App\Entity\Stock;
use App\Entity\StockMovement;
use App\Enum\StockMovementType;
use App\Repository\StockRepository;
use Doctrine\ORM\EntityManagerInterface;

class StockService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private StockRepository $stockRepository
    ) {
    }

    public function initialize(ProductVariant $variant, int $initialQty = 0): Stock
    {
        $stock = new Stock();
        $stock->setVariant($variant);
        $this->entityManager->persist($stock);

        if ($initialQty > 0) {
            $this->registerMovement($stock, StockMovementType::ENTRY, $initialQty, 'Stock initial');
        }

        return $stock;
    }

    public function getStockForVariant(ProductVariant $variant): ?Stock
    {
        return $this->stockRepository->findByVariant($variant);
    }

    public function restock(Stock $stock, int $qty, ?string $reason = null): void
    {
        $this->registerMovement($stock, StockMovementType::ENTRY, $qty, $reason);
    }

    public function reserveForSale(Stock $stock, int $qty, Purchase $purchase): void
    {
        if ($stock->getQty() < $qty) {
            throw new \RuntimeException(sprintf(
                'Stock insuffisant pour "%s" (demandé: %d, disponible: %d)',
                $stock->getVariant()->getProduct()->getName(),
                $qty,
                $stock->getQty()
            ));
        }

        $this->registerMovement($stock, StockMovementType::SALE, $qty, null, $purchase);
    }

    public function returnStock(Stock $stock, int $qty, Purchase $purchase, ?string $reason = null): void
    {
        $this->registerMovement($stock, StockMovementType::BACK, $qty, $reason, $purchase);
    }

    public function adjust(Stock $stock, int $delta, string $reason): void
    {
        $type = $delta >= 0 ? StockMovementType::ENTRY : StockMovementType::OUTING;
        $this->registerMovement($stock, $type, abs($delta), $reason);
    }

    private function registerMovement(
        Stock $stock,
        StockMovementType $type,
        int $qty,
        ?string $reason = null,
        ?Purchase $purchase = null
    ): void {
        $movement = new StockMovement();
        $movement->setStock($stock);
        $movement->setType($type);
        $movement->setQty($qty);
        $movement->setReason($reason);
        $movement->setPurchase($purchase);
        $movement->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($movement);

        $signed = in_array($type, [StockMovementType::OUTING, StockMovementType::SALE], true) ? -$qty : $qty;

        $stock->setQty($stock->getQty() + $signed);
        $stock->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->flush();
    }
}
