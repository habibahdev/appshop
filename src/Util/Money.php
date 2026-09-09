<?php

namespace App\Util;

/**
 * Utilitaire de validation pour les calculs monétaires bcmath.
 *
 * @package App\Util
 */
final class Money
{
    /**
     * Garantit qu'une chaine est numériquement exploitable par bcmath.
     *
     * Les getters Doctrine renvoient `?string` même pour une colonne `NOT NULL`
     * en base - cette méthode transforme cette incertitude en exception explicite
     * plutot que de laisser bcmul/bcadd/bccomp échouer silencieusement sur une valeur
     * non numérique.
     *
     * @param string|null $value Valeur à valider.
     * @param string $context Description utilisée dans le message d'erreur.
     * @return numeric-string
     * @throws \UnexpectedValueException Si $value est null ou non numérique.
     */
    public static function assertNumericString(?string $value, string $context = 'valeur monétaire'): string
    {
        if ($value === null || !is_numeric($value)) {
            throw new \UnexpectedValueException(sprintf('%s invalide ou manquante.', ucfirst($context)));
        }

        return $value;
    }
}
