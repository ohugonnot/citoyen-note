<?php

namespace App\Helper;

use App\Entity\ServicePublic;

class ServicePublicJsonHelper
{
    public static function build(ServicePublic $service, ?string $context = null): array
    {
        $data = [
            'id' => $service->getId()->toRfc4122(),
            'nom' => $service->getNom(),
            'description' => $service->getDescription(),
            'slug' => $service->getSlug(),
            'adresse' => $service->getAdresseComplete(),
            'code_postal' => $service->getCodePostal(),
            'ville' => $service->getVille(),
            'telephone' => $service->getTelephone(),
            'email' => $service->getEmail(),
            'site_web' => $service->getSiteWeb(),
            'horaires_ouverture' => $service->getHorairesOuverture(),
            'accessibilite_pmr' => $service->isAccessibilitePmr(),
            'score' => $service->getScore(),
            'statut' => $service->getStatut()->value, // Enum value
            'createdAt' => $service->getCreatedAt()?->format('c'),
            'updatedAt' => $service->getUpdatedAt()?->format('c'),
        ];

        // Coordonnées (toujours incluses si disponibles)
        if ($service->getLatitude() && $service->getLongitude()) {
            $data['coordinates'] = [
                'latitude' => (float)$service->getLatitude(),
                'longitude' => (float)$service->getLongitude()
            ];
        } else {
            $data['coordinates'] = null;
        }

        // Catégorie (toujours incluse)
        if ($service->getCategorie()) {
            $data['categorie'] = [
                'id' => $service->getCategorie()->getId()->toRfc4122(),
                'nom' => $service->getCategorie()->getNom(),
                'slug' => $service->getCategorie()->getSlug() ?? null,
                'icone' => $service->getCategorie()->getIcone(),
                'couleur' => $service->getCategorie()->getCouleur(),
            ];
        } else {
            $data['categorie'] = null;
        }

        // Statistiques d'évaluation (contexte dépendant)
        if ($context === 'with_stats') {
            $data['statistiques'] = [
                'note_moyenne' => $service->getNoteMoyenne(),
                'nombre_evaluations' => $service->getNombreEvaluations(),
            ];
        }


        return $data;
    }

    /**
     * Version light pour les listes/cartes
     */
    public static function buildLight(ServicePublic $service): array
    {
        $data = [
            'id' => $service->getId()->toRfc4122(),
            'nom' => $service->getNom(),
            'slug' => $service->getSlug(),
            'ville' => $service->getVille(),
            'code_postal' => $service->getCodePostal(),
            'note_moyenne' => null,
            'nombre_evaluations' => null,
            'accessibilite_pmr' => $service->isAccessibilitePmr(),
            'statut' => $service->getStatut()->value,
        ];

        // Coordonnées pour la carte
        if ($service->getLatitude() && $service->getLongitude()) {
            $data['coordinates'] = [
                'latitude' => (float)$service->getLatitude(),
                'longitude' => (float)$service->getLongitude()
            ];
        }

        // Catégorie simplifiée
        if ($service->getCategorie()) {
            $data['categorie'] = [
                'id' => $service->getCategorie()->getId()->toRfc4122(),
                'nom' => $service->getCategorie()->getNom(),
            ];
        }

        return $data;
    }

    /**
     * Version pour embed dans d'autres entités (évaluations par exemple)
     */
    public static function buildEmbed(ServicePublic $service): array
    {
        return [
            'id' => $service->getId()->toRfc4122(),
            'nom' => $service->getNom(),
            'slug' => $service->getSlug(),
            'ville' => $service->getVille(),
        ];
    }
}
