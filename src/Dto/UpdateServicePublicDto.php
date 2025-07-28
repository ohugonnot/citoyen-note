<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateServicePublicDto
{
    #[Assert\Length(max: 255, maxMessage: 'Le nom ne peut dépasser {{ limit }} caractères')]
    public ?string $nom = null;

    #[Assert\Length(max: 1000, maxMessage: 'La description ne peut dépasser {{ limit }} caractères')]
    public ?string $description = null;

    public ?string $adresse = null;

    #[Assert\Regex(pattern: '/^\d{5}$/', message: 'Le code postal doit contenir 5 chiffres')]
    public ?string $code_postal = null;

    public ?string $ville = null;

    #[Assert\Email(message: 'Email invalide')]
    public ?string $email = null;

    #[Assert\Regex(pattern: '/^[\d\s\.\-\+\(\)]+$/', message: 'Numéro de téléphone invalide')]
    public ?string $telephone = null;

    #[Assert\Url(message: 'URL invalide')]
    public ?string $site_web = null;

    #[Assert\Range(min: -90, max: 90, notInRangeMessage: 'Latitude invalide')]
    public ?float $latitude = null;

    #[Assert\Range(min: -180, max: 180, notInRangeMessage: 'Longitude invalide')]
    public ?float $longitude = null;

    public ?string $horaires = null;

    public ?bool $accessibilite_pmr = null;

    #[Assert\Choice(choices: ['actif', 'ferme', 'travaux'], message: 'Statut invalide')]
    public ?string $statut = null;

    public ?string $type_service = null;

    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key) && $value !== null) {
                $this->$key = $value;
            }
        }
    }

    public function toArray(): array
    {
        $result = [];
        foreach (get_object_vars($this) as $key => $value) {
            if ($value !== null) {
                $result[$key] = $value;
            }
        }
        return $result;
    }
}
