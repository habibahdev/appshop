<?php

namespace App\Enum;

/**
 * État du cycle de vie d'une {@see \App\Entity\Purchase}, piloté par le
 * composant Workflow Symfony `purchase_status`.
 *
 * @package App\Enum
 */
enum PurchaseStatus: string
{
    case PENDING = 'en_attente_paiement';
    case PAID = 'payee';
    case PREPARATION = 'en_preparation';
    case SHIPPED = 'expediee';
    case DELIVERED = 'livree';
    case CANCELLED = 'annulee';
    case REFUNDED = 'remboursee';

    public function label(): string
    {
        return match ($this) {
            self::CANCELLED => 'Annulée',
            self::DELIVERED => 'Livrée',
            self::SHIPPED => 'Expédiée',
            self::PENDING => 'En attente de paiement',
            self::PREPARATION => 'En préparation',
            self::REFUNDED => 'Remboursée',
            self::PAID => 'Payée'
        };
    }
}
