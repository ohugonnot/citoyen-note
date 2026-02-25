<?php

namespace App\Helper;

use App\Entity\Evaluation;

class EvaluationJsonHelper
{
    public static function build(Evaluation $evaluation): array
    {
        return [
            'uuid' => $evaluation->getUuid()->toRfc4122(),
            'note' => $evaluation->getNote(),
            'commentaire' => $evaluation->getCommentaire(),
            'criteres_specifiques' => $evaluation->getCriteresSpecifiques(),
            'statut' => $evaluation->getStatut()->value,
            'est_anonyme' => $evaluation->isEstAnonyme(),
            'est_verifie' => $evaluation->isEstVerifie(),
            'nombre_utile' => $evaluation->getNombreUtile(),
            'nombre_signalement' => $evaluation->getNombreSignalement(),
            'createdAt' => $evaluation->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updatedAt' => $evaluation->getUpdatedAt()?->format('Y-m-d H:i:s'),
            'pseudo' => $evaluation->getPseudo(),
            'service' => [
                'id' => $evaluation->getServicePublic()->getId()->toRfc4122(),
                'nom' => $evaluation->getServicePublic()->getNom(),
            ],
            'user' => !$evaluation->getUser() ? null : [
                'id' => $evaluation->getUser()?->getId(),
                'pseudo' => $evaluation->getUser()?->getPseudo(),
            ],
        ];
    }
}