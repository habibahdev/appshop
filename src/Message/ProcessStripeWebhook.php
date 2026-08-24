<?php

namespace App\Message;

class ProcessStripeWebhook
{
    public function __construct(
        public readonly string $purchaseReference,
        public readonly string $paymentIntentId
    ) {
    }
}
