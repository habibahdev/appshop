<?php

namespace App\Util;

final class Money
{
    /**
     * Valide qu'une chaine représente un nombre décimal exploitable par bcmath,
     * et lève une exception explicite.
     *
     * @return numeric-string
     */
    public static function assertNumericString(?string $value, string $context = 'valeur monétaire'): string
    {
        if ($value === null || !is_numeric($value)) {
            throw new \UnexpectedValueException(sprintf('%s invalide ou manquante.', ucfirst($context)));
        }

        return $value;
    }
}
