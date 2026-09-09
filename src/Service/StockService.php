<?php

namespace App\Service;

use App\Entity\ProductVariant;
use App\Entity\Purchase;
use App\Entity\Stock;
use App\Entity\StockMovement;
use App\Enum\StockMovementType;
use App\Repository\StockRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Point de passage unique pour toute variation de {@see Stock::$qty}.
 *
 * Garantit qu'un {@see StockMovement} est créé à chaque changement,
 * assurant la traçabilité complète des mouvements de stock.
 *
 * @package App\Service
 */
class StockService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private StockRepository $stockRepository
    ) {
    }

    /**
     * Crée l'enregistrement de stock pour une variante qui n'en possède pas encore.
     *
     * @param ProductVariant $variant Variante à initialiser.
     * @param integer $initialQty Quantité de départ (crée un mouvement ENTRY si > 0).
     * @return Stock
     */
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

    /**
     * Retourne le stock associé, null si non initialisé.
     *
     * @param ProductVariant $variant Variante dont on cherche le stock.
     * @return Stock|null
     */
    public function getStockForVariant(ProductVariant $variant): ?Stock
    {
        return $this->stockRepository->findOneByVariant($variant);
    }

    /**
     * Enregistre une réception de marchandise (mouvement ENTRY).
     *
     * @param Stock $stock Stock à réapprovisionner.
     * @param integer $qty Quantité reçue.
     * @param string|null $reason Motifi optionnel.
     * @return void
     */
    public function restock(Stock $stock, int $qty, ?string $reason = null): void
    {
        $this->registerMovement($stock, StockMovementType::ENTRY, $qty, $reason);
    }

    /**
     * Décrémente le stock suite à une vente (mouvement SALE).
     *
     * @param Stock $stock Stock à décrémenter.
     * @param integer $qty Quantité vendue.
     * @param Purchase $purchase Commande à l'origine de la vente.
     * @return void
     */
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

    /**
     * Réintègre du stock suite à un retour client (mouvement BACK).
     *
     * @param Stock $stock Stock à créditer.
     * @param integer $qty Quantité retournée.
     * @param Purchase $purchase Commande d'origine du retour.
     * @param string|null $reason Motif optionnel.
     * @return void
     */
    public function returnStock(Stock $stock, int $qty, Purchase $purchase, ?string $reason = null): void
    {
        $this->registerMovement($stock, StockMovementType::BACK, $qty, $reason, $purchase);
    }

    /**
     * Correction manuelle du stock (inventaire, casse, erreur de saisie).
     *
     * @param Stock $stock Stock à ajuster.
     * @param integer $delta Variation, positive (ENTRY) ou négative (OUTING)?
     * @param string $reason Motifi obligatoire (traçabilité de la correction).
     * @return void
     */
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
