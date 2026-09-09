<?php

namespace App\Service;

use App\Util\Money;
use App\Entity\ProductVariant;
use App\Repository\StockRepository;
use App\Repository\ProductVariantRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Panier d'achat stocké en session HTTP (clé `cart`, jamais persisté en base).
 *
 * Les {@see ProductVariant} sont rehydratés depuis la session à chaque appel
 * de {@see getItems()} - le panier reflète donc toujours le prix courant du
 * produits, jamais un prix figé au moment de l'ajout.
 *
 * @package App\Service
 */
class CartService
{
    private const SESSION_KEY = 'cart';

    public function __construct(
        private RequestStack $requestStack,
        private ProductVariantRepository $variantRepository,
        private StockRepository $stockRepository
    ) {
    }

    /**
     * Ajoute une variante au panier.
     *
     * La quantité demandée est automatiquement plafonnée au stock
     * disponible (résolu via {@see \App\Repository\StockRepository}).
     *
     * @param integer $variantId Identifiant de la {@see ProductVariant}.
     * @param integer $qty Quantité à ajouter (par défaut 1).
     * @return void
     * @throws \InvalidArgumentException Si la variante n'existe pas ou n'est pas active.
     */
    public function add(int $variantId, int $qty = 1): void
    {
        $variant = $this->variantRepository->find($variantId);
        if (!$variant || !$variant->isActive()) {
            throw new \InvalidArgumentException('Variant introuvable');
        }

        $stock = $this->stockRepository->findOneByVariant($variant);

        $available = $stock?->getQty() ?? 0;

        $cart = $this->getRaw();

        $currentQty = $cart[$variantId] ?? 0;

        $newQty = $currentQty + $qty;

        if ($newQty > $available) {
            $newQty = $available;
        }

        if ($newQty <= 0) {
            unset($cart[$variantId]);
        } else {
            $cart[$variantId] = $newQty;
        }

        $this->save($cart);
    }

    /**
     * Modifie la quantité d'une ligne du panier.
     *
     * @param integer $variantId Identifiant de la {@see ProductVariant}.
     * @param integer $qty Nouvelle quantité ; qty <= 0 retire l'article du panier.
     * @return void
     */
    public function updateQty(int $variantId, int $qty): void
    {
        $cart = $this->getRaw();

        if ($qty <= 0) {
            unset($cart[$variantId]);
            $this->save($cart);
            return;
        }

        $variant = $this->variantRepository->find($variantId);
        if ($variant) {
            $stock = $this->stockRepository->findOneByVariant($variant);
            $qty = min($qty, $stock?->getQty() ?? 0);
        }

        $cart[$variantId] = $qty;

        $this->save($cart);
    }

    /**
     * @param integer $variantId Identifiant de la {@see ProductVariant} à retirer.
     * @return void
     */
    public function remove(int $variantId): void
    {
        $cart = $this->getRaw();
        unset($cart[$variantId]);

        $this->save($cart);
    }

    /**
     * Vide entièrement le panier (appelé après validation de commande)
     *
     * @return void
     */
    public function clear(): void
    {
        $this->getSession()->remove(self::SESSION_KEY);
    }

    /**
     * Contenu détaillé du panier, rehydraté depuis la sassion.
     *
     * @return array<int, array{variant: ProductVariant, qty: int, subtotal: numeric-string}>
     */
    public function getItems(): array
    {
        $cart = $this->getRaw();
        if (empty($cart)) {
            return [];
        }

        $variants = $this->variantRepository->findBy(['id' => array_keys($cart)]);
        $items = [];
        foreach ($variants as $variant) {
            $id = $variant->getId();
            if ($id === null) {
                continue;
            }

            $qty = $cart[$id] ?? 0;
            if ($qty <= 0) {
                continue;
            }

            $price = Money::assertNumericString($variant->getPrice(), sprintf('prix de la variante #%d', $id));

            $items[] = [
                'variant' => $variant,
                'qty' => $qty,
                'subtotal' => bcmul($price, (string) $qty, 2)
            ];
        }

        return $items;
    }

    /**
     * Total du panier, calculé en bcmath pour garantir la précision décimale.
     *
     * @return numeric-string
     */
    public function getTotal(): string
    {
        $total = '0.00';
        foreach ($this->getItems() as $item) {
            $total = bcadd($total, $item['subtotal'], 2);
        }

        return $total;
    }

    /**
     * Somme des quantités de toutes les lignes du panier.
     *
     * @return integer
     */
    public function getTotalQty(): int
    {
        return array_sum($this->getRaw());
    }

    /**
     * True si le panier ne contient aucun article.
     *
     * @return boolean
     */
    public function isEmpty(): bool
    {
        return empty($this->getRaw());
    }

    private function getSession(): SessionInterface
    {
        return $this->requestStack->getSession();
    }

    /** @return array<int, int> [variantId => quantity] */
    private function getRaw(): array
    {
        return $this->getSession()->get(self::SESSION_KEY, []);
    }

    /**
     * @param array<int, int> $cart
     */
    private function save(array $cart): void
    {
        $this->getSession()->set(self::SESSION_KEY, $cart);
    }
}
