<?php

namespace App\MessageHandler;

use App\Enum\PurchaseStatus;
use App\Message\ProcessStripeWebhook;
use App\Repository\PurchaseRepository;
use App\Service\PurchaseService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ProcessStripeWebhookHandler
{
    public function __construct(
        private PurchaseRepository $purchaseRepository,
        private PurchaseService $purchaseService
    ) {
    }

    public function __invoke(ProcessStripeWebhook $message): void
    {
        $purchase = $this->purchaseRepository->findOneBy(['reference' => $message->purchaseReference]);
        if (!$purchase || $purchase->getStatus() !== PurchaseStatus::PENDING) {
            return;
        }

        $this->purchaseService->markAsPaid($purchase, $message->paymentIntentId);
        //$this->bus->dispatch(new GenerateInvoice($purchase->getId()));
    }
}
