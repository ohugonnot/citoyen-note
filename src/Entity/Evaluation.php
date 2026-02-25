<?php
// src/Entity/Evaluation.php

namespace App\Entity;

use App\Entity\Traits\TimestampableTrait;
use App\Enum\StatutEvaluation;
use App\Repository\EvaluationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EvaluationRepository::class)]
#[ORM\Table(name: 'evaluation')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(columns: ['service_public_id', 'note'], name: 'idx_service_note')]
class Evaluation
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $uuid;

    #[ORM\Column(type: 'smallint')]
    #[Assert\NotNull]
    #[Assert\Range(min: 1, max: 5)]
    private int $note;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(max: 2000)]
    private ?string $commentaire = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $criteresSpecifiques = null;

    #[ORM\Column(type: 'string', length: 20, enumType: StatutEvaluation::class)]
    private StatutEvaluation $statut = StatutEvaluation::ACTIVE;

    #[ORM\Column(type: 'boolean')]
    private bool $estAnonyme = false;

    #[ORM\Column(type: 'boolean')]
    private bool $estVerifie = false;

    #[ORM\Column(type: 'integer')]
    private int $nombreUtile = 0;

    #[ORM\Column(type: 'integer')]
    private int $nombreSignalement = 0;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $pseudo = null;

    #[ORM\Column(type: 'string', length: 45, nullable: true)]
    private ?string $ip = null;

    // Relations
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'evaluations')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user;

    #[ORM\ManyToOne(targetEntity: ServicePublic::class, inversedBy: 'evaluations')]
    #[ORM\JoinColumn(nullable: false, referencedColumnName: 'id',  onDelete: 'CASCADE')]
    private ServicePublic $servicePublic;

    public function __construct()
    {
        $this->uuid = Uuid::v7();
    }

    // Getters et Setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(?string $ip): self
    {
        $this->ip = $ip !== null ? hash('sha256', $ip) : null;
        return $this;
    }

    public function getUuid(): Uuid
    {
        return $this->uuid;
    }

    public function getNote(): int
    {
        return $this->note;
    }

    public function setNote(int $note): static
    {
        $this->note = $note;
        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): static
    {
        $this->commentaire = $commentaire;
        return $this;
    }

    public function getCriteresSpecifiques(): ?array
    {
        return $this->criteresSpecifiques;
    }

    public function setCriteresSpecifiques(?array $criteresSpecifiques): static
    {
        $this->criteresSpecifiques = $criteresSpecifiques;
        return $this;
    }

    public function getStatut(): StatutEvaluation
    {
        return $this->statut;
    }

    public function setStatut(StatutEvaluation $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function isEstAnonyme(): bool
    {
        return $this->estAnonyme;
    }

    public function setEstAnonyme(bool $estAnonyme): static
    {
        $this->estAnonyme = $estAnonyme;
        return $this;
    }

    public function isEstVerifie(): bool
    {
        return $this->estVerifie;
    }

    public function setEstVerifie(bool $estVerifie): static
    {
        $this->estVerifie = $estVerifie;
        return $this;
    }

    public function getNombreUtile(): int
    {
        return $this->nombreUtile;
    }

    public function setNombreUtile(int $nombreUtile): static
    {
        $this->nombreUtile = $nombreUtile;
        return $this;
    }

    public function getNombreSignalement(): int
    {
        return $this->nombreSignalement;
    }

    public function setNombreSignalement(int $nombreSignalement): static
    {
        $this->nombreSignalement = $nombreSignalement;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getServicePublic(): ServicePublic
    {
        return $this->servicePublic;
    }

    public function setServicePublic(ServicePublic $servicePublic): static
    {
        $this->servicePublic = $servicePublic;
        return $this;
    }

    // Méthodes métier
    public function incrementerUtile(): void
    {
        $this->nombreUtile++;
    }

    public function incrementerSignalement(): void
    {
        $this->nombreSignalement++;
    }

    public function estSupprimable(): bool
    {
        return $this->statut === StatutEvaluation::ACTIVE && 
               $this->nombreSignalement < 3;
    }

    public function setPseudo(?string $pseudo): static
    {
        $this->pseudo = $pseudo;
        return $this;
    }

    public function getPseudo(): string
    {
        if ($this->estAnonyme || $this->pseudo) {
            return $this->pseudo ?? 'Utilisateur anonyme';
        }
        return $this->getUser()?->getPseudo() ?? 'Utilisateur anonyme';
    }
}
