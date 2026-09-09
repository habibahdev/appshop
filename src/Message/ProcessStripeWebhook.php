<?php

namespace App\Message;

/**
 * Message déclenchant le traitement d'un webhook Stripe confirmé.
 *
 * @package App\Message
 * @see \App\MessageHandler\ProcessStripeWebhookHandler
 */
class ProcessStripeWebhook
{
    /**
     * @param string $purchaseReference Référence publique de la commande concernée.
     * @param string $paymentIntentId Identifiant du PaymentIntentStripe.
     */
    public function __construct(
        public readonly string $purchaseReference,
        public readonly string $paymentIntentId
    ) {
    }
}
