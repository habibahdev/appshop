<?php

namespace App\Message;

/**
 * Message déclanchant la génération asynchrone d'une facture PDF.
 *
 * @package App\Message
 * @see \App\MessageHandler\GenerateInvoiceHandler
 */
class GenerateInvoice
{
    /** @param int $purchaseId Identifiant de la commande à facturer. */
    public function __construct(public readonly int $purchaseId)
    {
    }
}
