<?php

namespace App\Message;

class GenerateInvoice
{
    public function __construct(public readonly int $purchaseId)
    {
    }
}
