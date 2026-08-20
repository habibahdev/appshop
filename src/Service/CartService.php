<?php

namespace App\Service;

use App\Util\Money;
use App\Entity\ProductVariant;
use App\Repository\StockRepository;
use App\Repository\ProductVariantRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CartService
{
    private const SESSION_KEY = 'cart';

    public function __construct(
        private RequestStack $requestStack,
        private ProductVariantRepository $variantRepository,
        private StockRepository $stockRepository
    ) {
    }

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

    public function remove(int $variantId): void
    {
        $cart = $this->getRaw();
        unset($cart[$variantId]);

        $this->save($cart);
    }

    public function clear(): void
    {
        $this->getSession()->remove(self::SESSION_KEY);
    }

    /**
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

    public function getTotalQty(): int
    {
        return array_sum($this->getRaw());
    }

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
