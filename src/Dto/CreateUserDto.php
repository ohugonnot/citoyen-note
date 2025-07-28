<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class CreateUserDto
{
    #[Assert\NotBlank(message: 'L\'email est requis')]
    #[Assert\Email(message: 'L\'email n\'est pas valide')]
    #[Assert\Length(max: 180, maxMessage: 'L\'email ne peut pas dépasser {{ limit }} caractères')]
    public readonly string $email;

    #[Assert\Length(min: 8, max: 4096, minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères')]
    public readonly ?string $password;

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
    public readonly array $roles;

    #[Assert\Range(min: 0, max: 100, notInRangeMessage: 'Le score de fiabilité doit être entre {{ min }} et {{ max }}')]
    public readonly ?int $scoreFiabilite;

    public readonly bool $sendWelcomeEmail;
    public readonly bool $isVerified;
    public readonly bool $accepteNewsletters;

    public function __construct(array $data)
    {
        $this->email = trim($data['email'] ?? '');
        $this->password = $data['password'] ?? null;
        $this->nom = trim($data['nom'] ?? '') ?: null;
        $this->prenom = trim($data['prenom'] ?? '') ?: null;
        $this->pseudo = trim($data['pseudo'] ?? '') ?: null;
        $this->telephone = trim($data['telephone'] ?? '') ?: null;
        $this->dateNaissance = trim($data['dateNaissance'] ?? '') ?: null;
        $this->codePostal = trim($data['codePostal'] ?? '') ?: null;
        $this->ville = trim($data['ville'] ?? '') ?: null;
        $this->roles = $data['roles'] ?? ['ROLE_USER'];
        $this->scoreFiabilite = isset($data['scoreFiabilite']) ? (int) $data['scoreFiabilite'] : null;
        $this->sendWelcomeEmail = (bool) ($data['sendWelcomeEmail'] ?? false);
        $this->isVerified = (bool) ($data['isVerified'] ?? false);
        $this->accepteNewsletters = (bool) ($data['accepteNewsletters'] ?? false);
    }
}
