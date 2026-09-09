<?php

namespace App\Enum;

/**
 * Nature d'un {@see \App\Entity\StockMovement}.
 *
 * @package App\Enum
 */
enum StockMovementType: string
{
    case ENTRY = 'entree';
    case OUTING = 'sortie';
    case SALE = 'vente';
    case BACK = 'retour';

    public function label(): string
    {
        return match ($this) {
            self::ENTRY => 'Entrée',
            self::OUTING => 'Sortie',
            self::SALE => 'Vente',
            self::BACK => 'Retour'
        };
    }

    public function isNegative(): bool
    {
        return in_array($this, [self::OUTING, self::SALE], true);
    }
}
