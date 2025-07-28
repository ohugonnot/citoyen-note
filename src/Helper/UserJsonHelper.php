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
            'prenom' => $user->getPrenom(),
            'nom' => $user->getNom(),
            'nomComplet' => self::buildNomComplet($user),
            'dateNaissance' => $user->getDateNaissance()?->format('Y-m-d'),
            'codePostal' => $user->getCodePostal(),
            'ville' => $user->getVille(),
            'telephone' => $user->getTelephone(),
            'roles' => $user->getRoles(),
            'isVerified' => $user->isVerified(),
            'verifiedAt' => $user->getVerifiedAt()?->format('c'),
            'accepteNewsletters' => $user->isAccepteNewsletters(),
            'scoreFiabilite' => $user->getScoreFiabilite(),
            'statut' => $user->getStatut()->value,
            'derniereConnexion' => $user->getDerniereConnexion()?->format('c'),
            'createdAt' => $user->getCreatedAt()?->format('c'),
            'updatedAt' => $user->getUpdatedAt()?->format('c'),
        ];
    }

    public static function buildMinimal(User $user): array
    {
        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'pseudo' => $user->getPseudo(),
            'nomComplet' => self::buildNomComplet($user),
            'statut' => $user->getStatut()->value,
            'isVerified' => $user->isVerified(),
            'createdAt' => $user->getCreatedAt()?->format('c'),
        ];
    }

    private static function buildNomComplet(User $user): ?string
    {
        $parts = array_filter([
            $user->getPrenom(),
            $user->getNom()
        ]);

        return !empty($parts) ? implode(' ', $parts) : null;
    }
}
