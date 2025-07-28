<?php

namespace App\Helper;

use App\Entity\ServicePublic;

class ServicePublicJsonHelper
{
    public static function build(ServicePublic $service): array
    {
        return [
            'id' => $service->getId(),
            'nom' => $service->getNom(),
            'description' => $service->getDescription(),
            'adresse_complete' => $service->getAdresseComplete(),
            'code_postal' => $service->getCodePostal(),
            'ville' => $service->getVille(),
            'telephone' => $service->getTelephone(),
            'email' => $service->getEmail(),
            'site_web' => $service->getSiteWeb(),
            'latitude' => $service->getLatitude(),
            'longitude' => $service->getLongitude(),
            'horaires_ouverture' => $service->getHorairesOuverture(),
            'accessibilite_pmr' => $service->isAccessibilitePmr(),
            'statut' => $service->getStatut(),
            'source_donnees' => $service->getSourceDonnees(),
            'note_moyenne' => $service->getNoteMoyenne(),
            'nombre_evaluations' => $service->getNombreEvaluations(),
            'categorie' => $service->getCategorie()?->getNom(),
            'createdAt' => $service->getCreatedAt()?->format('c'),
            'updatedAt' => $service->getUpdatedAt()?->format('c'),
        ];
    }
}
