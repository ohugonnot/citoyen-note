<?php

namespace App\Enum;

enum StatutService: string
{
    case ACTIF = 'actif';
    case FERME = 'ferme';
    case TRAVAUX = 'travaux';
    case SUSPENDU = 'suspendu';

    public function getLabel(): string
    {
        return match($this) {
            self::ACTIF => 'Actif',
            self::FERME => 'Fermé',
            self::TRAVAUX => 'En travaux',
            self::SUSPENDU => 'Suspendu',
        };
    }

    public function getBadgeClass(): string
    {
        return match($this) {
            self::ACTIF => 'badge-success',
            self::FERME => 'badge-danger',
            self::TRAVAUX => 'badge-warning',
            self::SUSPENDU => 'badge-secondary',
        };
    }

    public function isVisible(): bool
    {
        return $this === self::ACTIF;
    }
}
