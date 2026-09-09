<?php

namespace App\Enum;

/**
 * État de modération d'un {@see \App\Entity\Review}.
 *
 * @package App\Enum
 */
enum ReviewStatus: string
{
    case PENDING = 'en_attente';
    case APPROVED = 'approuve';
    case REJECTED = 'rejete';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'En attente',
            self::APPROVED => 'Approuvé',
            self::REJECTED => 'Rejeté'
        };
    }
}
