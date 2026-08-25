<?php

namespace App\MessageHandler;

use App\Message\GenerateInvoice;
use App\Repository\PurchaseRepository;
use App\Service\InvoiceService;

class GenerateInvoiceHandler
{
    public function __construct(
        private PurchaseRepository $purchaseRepository,
        private InvoiceService $invoiceService
    ) {
    }

    public function __invoke(GenerateInvoice $message): void
    {
        $purchase = $this->purchaseRepository->find($message->purchaseId);
        if (!$purchase) {
            return;
        }

        $this->invoiceService->generate($purchase);
    }
}
