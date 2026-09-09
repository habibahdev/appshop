<?php

namespace App\Security\Voter;

use App\Entity\Purchase;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Contrôle d'accès aux commandes : le propriétaire de la commande ou un
 * administrateur (ROLE_ADMIN, pour le support client) peuvent la consulter.
 *
 * @package App\Security\Voter
 * @extends Voter<string, Purchase>
 */
class PurchaseVoter extends Voter
{
    public const VIEW = 'view';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::VIEW && $subject instanceof Purchase;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var Purchase $purchase */
        $purchase = $subject;

        return $purchase->getUser() === $user || in_array('ROLE_ADMIN', $user->getRoles(), true);
    }
}
