<?php

namespace App\MessageHandler;

use App\Enum\PurchaseStatus;
use App\Message\GenerateInvoice;
use App\Service\PurchaseService;
use App\Message\ProcessStripeWebhook;
use App\Repository\PurchaseRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Traite un webhook Stripe confirmé de facçon asynchrone.
 *
 * Idempotent : si la commande n'existe pas ou n'est plus au statu
 * {@see PurchaseStatus::PENDING}, ne fait rien (protége contre un double
 * traitement si Stripe renvoie le même événement deux fois.)
 *
 * @package App\MessageHandler
 */
#[AsMessageHandler]
class ProcessStripeWebhookHandler
{
    public function __construct(
        private PurchaseRepository $purchaseRepository,
        private PurchaseService $purchaseService,
        private MessageBusInterface $bus
    ) {
    }

    /**
     * @param ProcessStripeWebhook $message Message à traiter.
     * @return void
     */
    public function __invoke(ProcessStripeWebhook $message): void
    {
        $purchase = $this->purchaseRepository->findOneBy(['reference' => $message->purchaseReference]);
        if (!$purchase || $purchase->getStatus() !== PurchaseStatus::PENDING) {
            return;
        }

        $this->purchaseService->markAsPaid($purchase, $message->paymentIntentId);
        $this->bus->dispatch(new GenerateInvoice($purchase->getId()));
    }
}
