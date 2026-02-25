<?php

namespace App\Helper;

use App\Entity\CategorieService;

class CategorieServiceJsonHelper
{
    public static function build(CategorieService $categorie): array
    {
        return [
            'id' => $categorie->getId()->toRfc4122(),
            'nom' => $categorie->getNom(),
            'description' => $categorie->getDescription(),
            'icone' => $categorie->getIcone(),
            'couleur' => $categorie->getCouleur(),
            'slug' => $categorie->getSlug(),
            'actif' => $categorie->isActif(),
            'ordre_affichage' => $categorie->getOrdreAffichage(),
      //      'note_moyenne' => $categorie->getNoteMoyenne(),
      //      'nombre_services' => $categorie->getServicesPublics()->count(),
            'createdAt' => $categorie->getCreatedAt()?->format('c'),
            'updatedAt' => $categorie->getUpdatedAt()?->format('c'),
        ];
    }
}
