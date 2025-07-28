<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateUserDto
{
    #[Assert\Positive(message: 'L\'ID doit être un nombre positif')]
    public readonly ?int $id;

    #[Assert\Email(message: 'L\'email n\'est pas valide')]
    #[Assert\Length(max: 180, maxMessage: 'L\'email ne peut pas dépasser {{ limit }} caractères')]
    public readonly ?string $email;

    #[Assert\Length(max: 100, maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères')]
    public readonly ?string $nom;

    #[Assert\Length(max: 100, maxMessage: 'Le prénom ne peut pas dépasser {{ limit }} caractères')]
    public readonly ?string $prenom;

    #[Assert\Length(max: 50, maxMessage: 'Le pseudo ne peut pas dépasser {{ limit }} caractères')]
    public readonly ?string $pseudo;

    #[Assert\Regex(pattern: '/^[0-9+\-\s()\.]+$/', message: 'Le numéro de téléphone n\'est pas valide')]
    public readonly ?string $telephone;

    public readonly ?string $dateNaissance;

    #[Assert\Length(max: 5, maxMessage: 'Le code postal ne peut pas dépasser {{ limit }} caractères')]
    public readonly ?string $codePostal;

    #[Assert\Length(max: 100, maxMessage: 'La ville ne peut pas dépasser {{ limit }} caractères')]
    public readonly ?string $ville;

    #[Assert\Choice(choices: ['ROLE_USER', 'ROLE_ADMIN', 'ROLE_MODERATOR', 'ROLE_SUPER_ADMIN'], multiple: true)]
    public readonly ?array $roles;

    #[Assert\Range(min: 0, max: 100, notInRangeMessage: 'Le score de fiabilité doit être entre {{ min }} et {{ max }}')]
    public readonly ?int $scoreFiabilite;

    public readonly ?bool $isVerified;
    public readonly ?bool $accepteNewsletters;
    public readonly ?string $statut;

    public function __construct(array $data)
    {
        $this->id = isset($data['id']) ? (int) $data['id'] : null;
        $this->email = isset($data['email']) ? trim($data['email']) : null;
        $this->nom = isset($data['nom']) ? (trim($data['nom']) ?: null) : null;
        $this->prenom = isset($data['prenom']) ? (trim($data['prenom']) ?: null) : null;
        $this->pseudo = isset($data['pseudo']) ? (trim($data['pseudo']) ?: null) : null;
        $this->telephone = isset($data['telephone']) ? (trim($data['telephone']) ?: null) : null;
        $this->dateNaissance = isset($data['dateNaissance']) ? (trim($data['dateNaissance']) ?: null) : null;
        $this->codePostal = isset($data['codePostal']) ? (trim($data['codePostal']) ?: null) : null;
        $this->ville = isset($data['ville']) ? (trim($data['ville']) ?: null) : null;
        $this->roles = $data['roles'] ?? null;
        $this->scoreFiabilite = isset($data['scoreFiabilite']) ? (int) $data['scoreFiabilite'] : null;
        $this->isVerified = isset($data['isVerified']) ? (bool) $data['isVerified'] : null;
        $this->accepteNewsletters = isset($data['accepteNewsletters']) ? (bool) $data['accepteNewsletters'] : null;
        $this->statut = isset($data['statut']) ? trim($data['statut']) : null;
    }
}
