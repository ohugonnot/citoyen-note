<?php

namespace App\Enum;

enum StatutUser: string
{
    case ACTIF = 'actif';
    case SUSPENDU = 'suspendu';
    case SUPPRIME = 'supprime';

    public function getLabel(): string
    {
        return match($this) {
            self::ACTIF => 'Actif',
            self::SUSPENDU => 'Suspendu',
            self::SUPPRIME => 'Supprimé',
        };
    }

    public function getBadgeClass(): string
    {
        return match($this) {
            self::ACTIF => 'badge-success',
            self::SUSPENDU => 'badge-warning',
            self::SUPPRIME => 'badge-danger',
        };
    }
}
