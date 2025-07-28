<?php
// src/Enum/StatutEvaluation.php

namespace App\Enum;

enum StatutEvaluation: string
{
    case ACTIVE = 'active';
    case MODEREE = 'moderee';
    case SIGNALEE = 'signalee';
    case SUPPRIMEE = 'supprimee';

    public function getLabel(): string
    {
        return match($this) {
            self::ACTIVE => 'Active',
            self::MODEREE => 'En modération',
            self::SIGNALEE => 'Signalée',
            self::SUPPRIMEE => 'Supprimée',
        };
    }

    public function getBadgeClass(): string
    {
        return match($this) {
            self::ACTIVE => 'badge-success',
            self::MODEREE => 'badge-warning',
            self::SIGNALEE => 'badge-danger',
            self::SUPPRIMEE => 'badge-secondary',
        };
    }
}
