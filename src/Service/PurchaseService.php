<?php

namespace App\Service;

use App\Entity\Detail;
use App\Entity\Purchase;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Workflow\WorkflowInterface;

class PurchaseService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CartService $cartService,
        private CouponService $couponService,
        private StockService $stockService,
        private WorkflowInterface $workflow
    ) {
    }

    public function createFromCart(User $user, string $delivery): Purchase
    {
        if ($this->cartService->isEmpty()) {
            throw new \RuntimeException('Le panier est vide');
        }

        foreach ($this->cartService->getItems() as $line) {
            $stock = $this->stockService->getStockForVariant($line['variant']);
            if (!$stock || $stock->getQty() < $line['qty']) {
                throw new \RuntimeException(
                    sprintf(
                        'Stock insuffisant pour "%s"',
                        $line['variant']->getProduct()->getName()
                    )
                );
            }
        }

        $purchase = new Purchase();
        $purchase->setUser($user);
        $purchase->setReference($this->generateReference());
        $purchase->setDelivery($delivery);

        foreach ($this->cartService->getItems() as $line) {
            $variant = $line['variant'];
            $detail = new Detail();
            $detail->setVariant($variant);
            $detail->setProductName($variant->getProduct()->getName());
            $detail->setVariantLabel($variant->getLabel());
            $detail->setProductPrice($variant->getPrice());
            $detail->setQty($line['qty']);
            $purchase->addDetail($detail);
        }

        $total = $this->cartService->getTotal();
        $coupon = $this->couponService->getApplied();
        $discount = $this->couponService->calculateDiscount($total, $coupon);
        $purchase->setTotal(bcsub($total, $discount, 2));
        $purchase->setDiscount($discount);
        if ($coupon) {
            $purchase->setCoupon($coupon);
            $coupon->incrementUsage();
            $this->couponService->remove();
        }

        $this->entityManager->persist($purchase);
        $this->entityManager->flush();

        foreach ($purchase->getDetails() as $detail) {
            $stock = $this->stockService->getStockForVariant($detail->getVariant());
            $this->stockService->reserveForSale($stock, $detail->getQty(), $purchase);
        }
        $this->cartService->clear();

        return $purchase;
    }

    public function markAsPaid(Purchase $purchase, string $paymentIntentId): void
    {
        $purchase->setStripe($paymentIntentId);
        $this->workflow->apply($purchase, 'payer');
        $this->entityManager->flush();
    }

    private function generateReference(): string
    {
        return 'CMD-' . date('Y') . '-' . str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
