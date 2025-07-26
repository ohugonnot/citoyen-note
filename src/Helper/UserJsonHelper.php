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
            'roles' => $user->getRoles(),
            'statut' => $user->getStatut()?->value,
            'isVerified' => $user->isVerified(),
            'scoreFiabilite' => $user->getScoreFiabilite(),
            'createdAt' => $user->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updatedAt' => $user->getUpdatedAt()?->format('Y-m-d H:i:s'),
            // Ajoutez d'autres champs selon vos besoins
        ];
    }
}
