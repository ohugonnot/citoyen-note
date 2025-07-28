<?php
// src/Entity/CategorieService.php

namespace App\Entity;

use App\Entity\Traits\TimestampableTrait;
use App\Repository\CategorieServiceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\DBAL\Types\Types;

#[ORM\Entity(repositoryClass: CategorieServiceRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'categorie_service')]
class CategorieService
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private string $nom;

    #[ORM\Column(nullable: false)]
    private bool $actif = true;

    #[ORM\Column(nullable: false)]
    private int $ordreAffichage = 0;


    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    private ?string $slug = null;
    
    public function generateSlug(): void
    {
        $slugger = new \Symfony\Component\String\Slugger\AsciiSlugger();
        $this->slug = $slugger->slug($this->nom)->lower()->toString();
    }
    
    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function onPrePersist(): void
    {
        $this->generateSlug();
    }

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $description = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Assert\Length(max: 50)]
    private ?string $icone = null;

    #[ORM\Column(length: 7, nullable: true)]
    #[Assert\Regex(pattern: '/^#[0-9A-Fa-f]{6}$/', message: 'La couleur doit être au format hexadécimal (#RRGGBB)')]
    private ?string $couleur = null;

    #[ORM\OneToMany(mappedBy: 'categorie', targetEntity: ServicePublic::class)]
    private Collection $servicesPublics;

    public function __construct()
    {
        $this->id = Uuid::v7();
        $this->servicesPublics = new ArrayCollection();
    }

    // Getters et Setters
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

    public function getIcone(): ?string
    {
        return $this->icone;
    }

    public function setIcone(?string $icone): static
    {
        $this->icone = $icone;
        return $this;
    }

    public function getCouleur(): ?string
    {
        return $this->couleur;
    }

    public function setCouleur(?string $couleur): static
    {
        $this->couleur = $couleur;
        return $this;
    }

    /**
     * @return Collection<int, ServicePublic>
     */
    public function getServicesPublics(): Collection
    {
        return $this->servicesPublics;
    }

    public function addServicePublic(ServicePublic $servicePublic): static
    {
        if (!$this->servicesPublics->contains($servicePublic)) {
            $this->servicesPublics->add($servicePublic);
            $servicePublic->setCategorie($this);
        }

        return $this;
    }

    public function removeServicePublic(ServicePublic $servicePublic): static
    {
        if ($this->servicesPublics->removeElement($servicePublic)) {
            // Set the owning side to null (unless already changed)
            if ($servicePublic->getCategorie() === $this) {
                $servicePublic->setCategorie(null);
            }
        }

        return $this;
    }

    // Méthodes utilitaires
    public function getNombreServices(): int
    {
        return $this->servicesPublics->count();
    }

    public function getNoteMoyenne(): ?float
    {
        $services = $this->servicesPublics->toArray();
        if (empty($services)) {
            return null;
        }

        $sommeNotes = 0;
        $nombreNotesValides = 0;

        foreach ($services as $service) {
            $note = $service->getNoteMoyenne();
            if ($note !== null) {
                $sommeNotes += $note;
                $nombreNotesValides++;
            }
        }

        return $nombreNotesValides > 0 ? round($sommeNotes / $nombreNotesValides, 2) : null;
    }

    public function __toString(): string
    {
        return $this->nom;
    }

     public function isActif(): bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): static
    {
        $this->actif = $actif;
        return $this;
    }

    public function getOrdreAffichage(): int
    {
        return $this->ordreAffichage;
    }

    public function setOrdreAffichage(int $ordreAffichage): static
    {
        $this->ordreAffichage = $ordreAffichage;
        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }
}
