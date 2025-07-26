<?php

namespace App\Helper;

use App\Entity\User;

class UserJsonHelper
{
    public static function build(User $user): array
    {
        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'pseudo' => $user->getPseudo(),
            'nom' => $user->getNom(),
            'prenom' => $user->getPrenom(),
            'telephone' => $user->getTelephone(),
            'dateNaissance' => $user->getDateNaissance()?->format('Y-m-d'),
            'createdAt' => $user->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updatedAt' => $user->getUpdatedAt()?->format('Y-m-d H:i:s'),
            'statut' => $user->getStatut()?->value,
            'roles' => $user->getRoles(),
            'isVerified' => $user->isVerified(),
            'scoreFiabilite' => $user->getScoreFiabilite(),
        ];
    }
}