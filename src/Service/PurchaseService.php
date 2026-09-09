<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Detail;
use App\Entity\Purchase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;

/**
 * Orchestrateur de la création de commande à partir du panier - point
 * d'entrée principal du tunnel d'achat.
 *
 * @package App\Service
 */
class PurchaseService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CartService $cartService,
        private CouponService $couponService,
        private StockService $stockService,
        #[Target('purchase_status')]
        private WorkflowInterface $workflow
    ) {
    }

    /**
     * Transforme le panier courant en commande persistée.
     *
     * Vérifie le coupon actif, décrémente le stock via
     * {@see \App\Service\StockService::reserveForSale()} pour chaque ligne
     * (réservation à la création de la commande, pas au paiement confirmé),
     * puis vide le panier.
     *
     * @param User $user Client passant la commande.
     * @param string $delivery Adresse de livraison (texte libre ou généré via
     * {@see \App\Entity\Address::toDeliveryString()}).
     * @return Purchase La commande créée, à l'état {@see PurchaseStatus::PENDING}.
     * @throws \RuntimeException Si le panier est vide ou si le stock est insuffisant pour au moins une ligne.
     */
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

    /**
     * Marque une commande comme payée suite à confirmation Stripe.
     *
     * Applique la transition `payer` du workflow `purchase_status`.
     * Doit être appelée uniquement depuis {@see \App\MessageHandler\ProcessStripeWebhookHandler}
     * (confirmation par webhook serveur-à-serveur), jamais depuis un contrôleur HTTP synchrone
     *
     * @param Purchase $purchase Commande à marquer comme payée.
     * @param string $paymentIntentId Identifiant du PaymentIntent Stripe
     * @return void
     */
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
