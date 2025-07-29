<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class CreateServicePublicDto
{
    #[Assert\NotBlank(message: 'Le nom est obligatoire')]
    #[Assert\Length(max: 255, maxMessage: 'Le nom ne peut dépasser {{ limit }} caractères')]
    public string $nom;

    #[Assert\Length(max: 1000, maxMessage: 'La description ne peut dépasser {{ limit }} caractères')]
    public ?string $description = null;

    #[Assert\NotBlank(message: 'L\'adresse est obligatoire')]
    public string $adresse;

    #[Assert\NotBlank(message: 'Le code postal est obligatoire')]
    #[Assert\Regex(pattern: '/^\d{5}$/', message: 'Le code postal doit contenir 5 chiffres')]
    public string $code_postal;

    #[Assert\NotBlank(message: 'La ville est obligatoire')]
    public string $ville;

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

    // 🚀 CHANGEMENT ICI : array au lieu de string
    public ?array $horaires = null;

    public ?bool $accessibilite_pmr = null;

    #[Assert\Choice(choices: ['actif', 'ferme', 'travaux'], message: 'Statut invalide')]
    public string $statut = 'actif';

    #[Assert\NotBlank(message: 'La catégorie est obligatoire')]
    public ?string $categorie = null;

    public ?string $source_donnees = 'admin';

    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    public function toArray(): array
    {
        return [
            'nom' => $this->nom,
            'description' => $this->description,
            'adresse' => $this->adresse,
            'code_postal' => $this->code_postal,
            'ville' => $this->ville,
            'email' => $this->email,
            'telephone' => $this->telephone,
            'site_web' => $this->site_web,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'horaires' => $this->horaires,
            'accessibilite_pmr' => $this->accessibilite_pmr,
            'statut' => $this->statut,
            'categorie' => $this->categorie,
            'source_donnees' => $this->source_donnees,
        ];
    }
}
