<?php

namespace App\Enum;

/**
 * Nature du calcul de réduction d'un {@see \Ap\Entity\Coupon}.
 *
 * @package App\Enum
 */
enum CouponType: string
{
    case PERCENTAGE = 'percentage';
    case FIXED = 'fixed';

    public function label(): string
    {
        return match ($this) {
            self::PERCENTAGE => 'Pourcentage',
            self::FIXED => 'Montant fixe'
        };
    }
}
