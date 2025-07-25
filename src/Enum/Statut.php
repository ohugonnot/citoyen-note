<?php

namespace App\Enum;

enum Statut: string
{
    case ACTIF = 'actif';
    case SUSPENDU = 'suspendu';
    case SUPPRIME = 'supprime';
}