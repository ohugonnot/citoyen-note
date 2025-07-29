<?php
// src/Entity/ServicePublic.php

namespace App\Entity;

use App\Entity\Traits\TimestampableTrait;
use App\Enum\StatutService;
use App\Enum\StatutEvaluation;
use App\Repository\ServicePublicRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ServicePublicRepository::class)]
#[ORM\Table(name: 'service_public')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(columns: ['ville', 'code_postal'], name: 'idx_localisation')]
#[ORM\Index(columns: ['categorie_id', 'statut'], name: 'idx_categorie_statut')]
class ServicePublic
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\Column(length: 200)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 200)]
    private string $nom;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 2000)]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $adresseComplete = null;

    #[ORM\Column(length: 5)]
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^\d{5}$/', message: 'Le code postal doit contenir exactement 5 chiffres')]
    private string $codePostal;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private string $ville;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 8, nullable: true)]
    #[Assert\Range(min: -90, max: 90)]
    private ?string  $latitude = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 11, scale: 8, nullable: true)]
    #[Assert\Range(min: -180, max: 180)]
    private ?string  $longitude = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Regex(pattern: '/^(?:\+33|0)[1-9](?:[0-9]{8})$/', message: 'Format de téléphone invalide')]
    private ?string $telephone = null;

    #[ORM\Column(length: 180, nullable: true)]
    #[Assert\Email]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Url]
    private ?string $siteWeb = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $horairesOuverture = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $accessibilitePmr = false;

    #[ORM\Column(length: 20, enumType: StatutService::class)]
    private StatutService $statut = StatutService::ACTIF;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $sourceDonnees = null;

    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    private ?string $slug = null;

    public function generateSlug(): void
    {
        $slugger = new \Symfony\Component\String\Slugger\AsciiSlugger();
        
        $parts = [$this->nom];
        if (isset($this->ville) && !empty($this->ville)) {
            $parts[] = $this->ville;
        }
        
        $baseSlug = implode(' ', $parts);
        $this->slug = $slugger->slug($baseSlug)->lower()->toString();
    }
    
    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function onPrePersist(): void
    {
        $this->generateSlug();
    }

    // Relations
    #[ORM\ManyToOne(targetEntity: CategorieService::class, inversedBy: 'servicesPublics')]
    #[ORM\JoinColumn(nullable: true)]
    private ?CategorieService $categorie = null;

    #[ORM\OneToMany(
        mappedBy: 'servicePublic', 
        targetEntity: Evaluation::class,
        cascade: ['remove'],  
        orphanRemoval: true   
    )]
    private Collection $evaluations;

    public function __construct()
    {
        $this->id = Uuid::v7();
        $this->evaluations = new ArrayCollection();
    }

    // Getters et Setters basiques
    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getAdresseComplete(): ?string
    {
        return $this->adresseComplete;
    }

    public function setAdresseComplete(?string $adresseComplete): static
    {
        $this->adresseComplete = $adresseComplete;
        return $this;
    }

    public function getCodePostal(): string
    {
        return $this->codePostal;
    }

    public function setCodePostal(string $codePostal): static
    {
        $this->codePostal = $codePostal;
        return $this;
    }

    public function getVille(): string
    {
        return $this->ville;
    }

    public function setVille(string $ville): static
    {
        $this->ville = $ville;
        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude !== null ? (float) $this->latitude : null;
    }

    public function setLatitude(?float $latitude): self
    {
        $this->latitude = $latitude !== null ? (string) $latitude : null;
        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude !== null ? (float) $this->longitude : null;
    }

    public function setLongitude(?float $longitude): self
    {
        $this->longitude = $longitude !== null ? (string) $longitude : null;
        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getSiteWeb(): ?string
    {
        return $this->siteWeb;
    }

    public function setSiteWeb(?string $siteWeb): static
    {
        $this->siteWeb = $siteWeb;
        return $this;
    }

    public function getHorairesOuverture(): ?array
    {
        return $this->horairesOuverture;
    }

    public function setHorairesOuverture(?array $horairesOuverture): static
    {
        $this->horairesOuverture = $horairesOuverture;
        return $this;
    }

    public function isAccessibilitePmr(): bool
    {
        return $this->accessibilitePmr;
    }

    public function setAccessibilitePmr(bool $accessibilitePmr): static
    {
        $this->accessibilitePmr = $accessibilitePmr;
        return $this;
    }

    public function getStatut(): StatutService
    {
        return $this->statut;
    }

    public function setStatut(StatutService $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getSourceDonnees(): ?string
    {
        return $this->sourceDonnees;
    }

    public function setSourceDonnees(?string $sourceDonnees): static
    {
        $this->sourceDonnees = $sourceDonnees;
        return $this;
    }

    public function getCategorie(): ?CategorieService
    {
        return $this->categorie;
    }

    public function setCategorie(?CategorieService $categorie): static
    {
        $this->categorie = $categorie;
        return $this;
    }

    /**
     * @return Collection<int, Evaluation>
     */
    public function getEvaluations(): Collection
    {
        return $this->evaluations;
    }

    public function addEvaluation(Evaluation $evaluation): static
    {
        if (!$this->evaluations->contains($evaluation)) {
            $this->evaluations->add($evaluation);
            $evaluation->setServicePublic($this);
        }

        return $this;
    }

    public function removeEvaluation(Evaluation $evaluation): static
    {
        if ($this->evaluations->removeElement($evaluation)) {
            // Au lieu de mettre à null, on change le statut
            if ($evaluation->getServicePublic() === $this) {
                $evaluation->setStatut(StatutEvaluation::SUPPRIMEE);
            }
        }

        return $this;
    }

    // Méthodes calculées et utilitaires
    public function getNoteMoyenne(): ?float
    {
        $evaluations = $this->evaluations->filter(fn($e) => $e->getStatut() === StatutEvaluation::ACTIVE);
        
        if ($evaluations->isEmpty()) {
            return null;
        }

        $somme = array_reduce($evaluations->toArray(), fn($carry, $eval) => $carry + $eval->getNote(), 0);
        return round($somme / $evaluations->count(), 2);
    }

    public function getNombreEvaluations(): int
    {
        return $this->evaluations->filter(fn($e) => $e->getStatut() === StatutEvaluation::ACTIVE)->count();
    }

    public function getEvaluationsActives(): Collection
    {
        return $this->evaluations->filter(fn($e) => $e->getStatut() === StatutEvaluation::ACTIVE);
    }

    public function getAdresseFormatee(): string
    {
        $adresse = $this->adresseComplete ?: '';
        return trim($adresse . ' ' . $this->codePostal . ' ' . $this->ville);
    }

    public function hasCoordonnees(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    public function estOuvert(): bool
    {
        return $this->statut === StatutService::ACTIF;
    }

    public function getDistanceFrom(?float $latitude, ?float $longitude): ?float
    {
        if (!$this->hasCoordonnees() || $latitude === null || $longitude === null) {
            return null;
        }

        // Formule de Haversine pour calculer la distance
        $earthRadius = 6371; // Rayon de la Terre en km
        
        $latFrom = deg2rad($latitude);
        $lonFrom = deg2rad($longitude);
        $latTo = deg2rad($this->latitude);
        $lonTo = deg2rad($this->longitude);

        $deltaLat = $latTo - $latFrom;
        $deltaLon = $lonTo - $lonFrom;

        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
             cos($latFrom) * cos($latTo) * sin($deltaLon / 2) * sin($deltaLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 2);
    }

    public function getDerniereEvaluation(): ?Evaluation
    {
        $evaluationsActives = $this->getEvaluationsActives();
        
        if ($evaluationsActives->isEmpty()) {
            return null;
        }

        $evaluationsArray = $evaluationsActives->toArray();
        usort($evaluationsArray, fn($a, $b) => $b->getDateCreation() <=> $a->getDateCreation());
        
        return $evaluationsArray[0];
    }

    public function __toString(): string
    {
        return $this->nom . ' (' . $this->ville . ')';
    }
}
