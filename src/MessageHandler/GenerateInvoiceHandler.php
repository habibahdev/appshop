<?php

namespace App\MessageHandler;

use App\Message\GenerateInvoice;
use App\Repository\PurchaseRepository;
use App\Service\InvoiceService;

/**
 * Déclenche la génération de facture PDF pour une commande donnée.
 *
 * @package App\MessageHandler
 */
class GenerateInvoiceHandler
{
    public function __construct(
        private PurchaseRepository $purchaseRepository,
        private InvoiceService $invoiceService
    ) {
    }

    /**
     * @param GenerateInvoice $message Message à traiter.
     * @return void
     */
    public function __invoke(GenerateInvoice $message): void
    {
        $purchase = $this->purchaseRepository->find($message->purchaseId);
        if (!$purchase) {
            return;
        }

        $this->invoiceService->generate($purchase);
    }
}
