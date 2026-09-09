<?php

namespace App\Service;

use Stripe\Event;
use App\Util\Money;
use Stripe\Webhook;
use App\Entity\Purchase;
use Stripe\StripeClient;
use Stripe\Checkout\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Encapsulation des appels au SDK Stripe (Checkout Sessions, vérification
 * de webhooks).
 *
 * @package App\Service
 */
class StripeService
{
    private StripeClient $stripeClient;

    public function __construct(
        private string $stripeSecretKey,
        private UrlGeneratorInterface $urlGenerator
    ) {
        $this->stripeClient = new StripeClient($this->stripeSecretKey);
    }

    /**
     * Crée une session de paiement Stripe Checkout pour une commande.
     *
     * @param Purchase $purchase Commande à facturer, dont les {@see Detail}
     * sont convertis en ligne items Stripe.
     * @return Session La session Stripe créée, redirigeant vers `session->url`.
     */
    public function createCheckoutSession(Purchase $purchase): Session
    {
        $lineItems = [];

        foreach ($purchase->getDetails() as $item) {
            $price = Money::assertNumericString(
                $item->getProductPrice(),
                sprintf('prix du détail de commande #%d', $item->getId() ?? 0)
            );

            $lineItems[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $item->getProductName() . ' - ' . $item->getVariantLabel(),
                    ],
                    'unit_amount' => (int) bcmul($price, '100', 0),
                ],
                'quantity' => $item->getQty(),
            ];
        }

        return $this->stripeClient->checkout->sessions->create([
            'mode' => 'payment',
            'line_items' => $lineItems,
            'success_url' => $this->urlGenerator
                ->generate(
                    'checkout_success',
                    ['reference' => $purchase->getReference()],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $this->urlGenerator
                ->generate(
                    'checkout_cancel',
                    [
                        'reference' => $purchase->getReference()
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),
            'client_reference_id' => $purchase->getReference(),
            'metadata' => ['purchase_reference' => $purchase->getReference()]
        ]);
    }

    /**
     * Vérifie la signature et décode un événement webhook Stripe.
     *
     * @param string $payload Corps brut de la requête webhook.
     * @param string $signature En-tête `Stripe-Signature`.
     * @param string $webhookSecret Secret de vérification configuré côté Stripe.
     * @return Event L'événement Stripe décodé.
     * @throws  \UnexpectedValueException Si le payload est invalide.
     * @throws  \Stripe\Exception\SignatureVerificationException Si la signature ne correspond pas.
     */
    public function constructWebhookEvent(string $payload, string $signature, string $webhookSecret): Event
    {
        return Webhook::constructEvent($payload, $signature, $webhookSecret);
    }
}
