<?php
// src/Service/ServicePublicManager.php

namespace App\Service;

use App\Dto\CreateServicePublicDto;
use App\Dto\UpdateServicePublicDto;
use App\Entity\CategorieService;
use App\Entity\Evaluation;
use App\Entity\ServicePublic;
use App\Enum\StatutService;
use App\Repository\CategorieServiceRepository;
use App\Repository\ServicePublicRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

class ServicePublicManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ServicePublicRepository $repository,
        private CategorieServiceRepository $categorieServiceRepository,
    ) {}

    public function creer(array $donnees): ServicePublic
    {
        $service = new ServicePublic();
        $this->hydraterDepuisDonnees($service, $donnees);
        
        $this->entityManager->persist($service);
        $this->entityManager->flush();
        
        return $service;
    }

    public function mettreAJour(ServicePublic $service, array $donnees): ServicePublic
    {
        $this->hydraterDepuisDonnees($service, $donnees);
        $this->entityManager->flush();
        
        return $service;
    }

    public function supprimer(ServicePublic $service): void
    {
        $this->entityManager->remove($service);
        $this->entityManager->flush();
    }

    public function rechercherParProximite(float $lat, float $lng, float $rayon = 10): array
    {
        $services = $this->repository->findByProximite($lat, $lng, $rayon);
        
        // Trier par distance réelle
        usort($services, function($a, $b) use ($lat, $lng) {
            $distanceA = $a->getDistanceDepuis($lat, $lng) ?? PHP_FLOAT_MAX;
            $distanceB = $b->getDistanceDepuis($lat, $lng) ?? PHP_FLOAT_MAX;
            return $distanceA <=> $distanceB;
        });
        
        return $services;
    }

    private function hydraterDepuisDonnees(ServicePublic $service, array $donnees): void
    {
        // Mapping direct des champs
        $mappingChamps = [
            'nom' => 'setNom',
            'adresse' => 'setAdresseComplete',
            'code_postal' => 'setCodePostal',
            'ville' => 'setVille',
            'telephone' => 'setTelephone',
            'description' => 'setDescription',
        ];
        
        foreach ($mappingChamps as $cle => $setter) {
            if (isset($donnees[$cle]) && !empty($donnees[$cle])) {
                $service->$setter($donnees[$cle]);
            }
        }
        
        // Traitements spéciaux
        $this->hydraterEmail($service, $donnees);
        $this->hydraterCoordonnees($service, $donnees);
        $this->hydraterHoraires($service, $donnees);
        $this->hydraterCategorie($service, $donnees);
        $this->hydraterStatut($service, $donnees);

        $service->setSourceDonnees('import_api');
    }

    private function hydraterEmail(ServicePublic $service, array $donnees): void
    {
        if (isset($donnees['email']) && filter_var($donnees['email'], FILTER_VALIDATE_EMAIL)) {
            $service->setEmail($donnees['email']);
        }
    }

    private function hydraterStatut(ServicePublic $service, array $donnees): void
    {
        if (isset($donnees['statut'])) {
            try {
                $statut = StatutService::from($donnees['statut']);
                $service->setStatut($statut);
            } catch (\ValueError $e) {
                $service->setStatut(StatutService::ACTIF);
            }
        } else {
            // Pas de statut fourni, utiliser ACTIF par défaut
            $service->setStatut(StatutService::ACTIF);
        }
    }

    private function hydraterCoordonnees(ServicePublic $service, array $donnees): void
    {
        if (isset($donnees['latitude']) && is_numeric($donnees['latitude'])) {
            $service->setLatitude((float) $donnees['latitude']);
        }
        
        if (isset($donnees['longitude']) && is_numeric($donnees['longitude'])) {
            $service->setLongitude((float) $donnees['longitude']);
        }
    }

    private function hydraterHoraires(ServicePublic $service, array $donnees): void
    {
        if (isset($donnees['horaires']) && !empty($donnees['horaires'])) {
            
            // 🎯 Si c'est déjà un array structuré (depuis le front)
            if (is_array($donnees['horaires'])) {
                $service->setHorairesOuverture($donnees['horaires']);
            } 
            // 🚀 Si c'est un string (compatibilité avec l'ancien système)
            elseif (is_string($donnees['horaires'])) {
                $service->setHorairesOuverture([
                    'texte' => trim($donnees['horaires'])
                ]);
            }
        }
    }

    private function hydraterCategorie(ServicePublic $service, array $donnees): void
    {
        $id = null;
        $nom = null;
        if (!empty($donnees["type_service"])) {
            $nom = $donnees["type_service"];
            $categorieNom = str_replace('É', 'E', $nom);
        }
        if (!empty($donnees["categorie"])) {
            $id = $donnees["categorie"];
        }
        
        if (empty($id) && empty($nom)) {
            return;
        }
        
        if (!empty($id)) {
            $categorie = $this->categorieServiceRepository->find($id);
            if ($categorie) {
                $service->setCategorie($categorie);
                return;
            }
        }

        if (!empty($nom)) {
            $categorie = $this->categorieServiceRepository->findOneBy(['nom' => $categorieNom]);
            if ($categorie) {
                $service->setCategorie($categorie);
                return;
            }
        }
    
        if (!$categorie && !empty($nom)) {
            $categorie = new CategorieService();
            
            $iconesMapping = [
                'Mairie' => 'bi-building',
                'Préfecture' => 'bi-building-check',       
                'Sous-Préfecture' => 'bi-building-gear',    
                'Hôpital' => 'bi-hospital',                 
                'CAF' => 'bi-people-fill',
                'Pôle Emploi' => 'bi-briefcase',          
                'CPAM' => 'bi-heart-pulse',
                'URSSAF' => 'bi-calculator',
                'Tribunal' => 'bi-scale',
                'Conseil Départemental' => 'bi-bank',     
                'Métropole' => 'bi-buildings',              
                'Centre des Impôts' => 'bi-receipt',        
                'Trésorerie' => 'bi-coin',                  
                'MDPH' => 'bi-person-wheelchair',
                'Police' => 'bi-shield-check',
                'Gendarmerie' => 'bi-shield-fill',
                'Éducation' => 'bi-mortarboard',            
                'Université' => 'bi-book',                
                'École' => 'bi-backpack',                   
                'Bibliothèque' => 'bi-journal-bookmark',   
                'Archives' => 'bi-archive'
            ];
            
            $couleursMapping = [
                'Mairie' => '#0d6efd',
                'Préfecture' => '#6610f2',
                'Sous-Préfecture' => '#6f42c1',
                'Hôpital' => '#dc3545',
                'CAF' => '#20c997',
                'Pôle Emploi' => '#fd7e14',
                'CPAM' => '#e83e8c',
                'URSSAF' => '#6c757d',
                'Tribunal' => '#495057',
                'Conseil Départemental' => '#198754',
                'Métropole' => '#0dcaf0',
                'Centre des Impôts' => '#ffc107',
                'Trésorerie' => '#fd7e14',
                'MDPH' => '#d63384',
                'Police' => '#0f5132',
                'Gendarmerie' => '#084298',
                'Éducation' => '#0a58ca',
                'Université' => '#6f42c1',
                'École' => '#0dcaf0',
                'Bibliothèque' => '#6610f2',
                'Archives' => '#495057'
            ];
            
            // 🔥 IMPORTANT : Mapper AVANT de transformer
            $icone = $iconesMapping[$nom] ?? 'bi-building';  // $nom avec accents
            $couleur = $couleursMapping[$nom] ?? '#6c757d';   // $nom avec accents
            
            // PUIS transformer pour la BDD

            $categorie->setNom($categorieNom);
            $categorie->setIcone($icone);
            $categorie->setCouleur($couleur);
            
            $this->entityManager->persist($categorie);
        }
        
        $service->setCategorie($categorie);
    }



    public function modifier(ServicePublic $service, UpdateServicePublicDto $dto): ServicePublic
    {
        return $this->mettreAJour($service, $dto->toArray());
    }

    public function creerDepuisDto(CreateServicePublicDto $dto): ServicePublic
    {
        return $this->creer($dto->toArray());
    }

    public function supprimerPlusieurs(array $ids): int
    {
        $count = 0;
        
        foreach ($ids as $id) {
            try {
                $uuid = Uuid::fromString($id);
                $service = $this->repository->find($uuid);
                
                if ($service) {
                    $this->entityManager->remove($service);
                    $count++;
                }
            } catch (\Exception $e) {
                // UUID invalide, on continue
                continue;
            }
        }
        
        if ($count > 0) {
            $this->entityManager->flush();
        }
        
        return $count;
    }

    public function importerDepuisCsv(string $cheminFichier, bool $viderAvant = true): array
    {
        $resultats = ['succes' => 0, 'erreurs' => [], 'supprimes' => 0];
        
        if ($viderAvant) {
            $resultats['supprimes'] = $this->viderTableServicePublic();
        }

        if (($handle = fopen($cheminFichier, 'r')) !== false) {
            $headers = fgetcsv($handle);
            
            while (($data = fgetcsv($handle)) !== false) {
                try {
                    $donnees = array_combine($headers, $data);
                    $this->creer($donnees);
                    $resultats['succes']++;
                } catch (\Exception $e) {
                    $resultats['erreurs'][] = [
                        'ligne' => $data,
                        'erreur' => $e->getMessage()
                    ];
                }
            }
            
            fclose($handle);
        }
        
        return $resultats;
    }

    private function viderTableServicePublic(): int
    {
        $services = $this->repository->findAll();
        $count = count($services);
        
        if ($count > 0) {
            foreach ($services as $service) {
                $this->entityManager->remove($service);
            }
            $this->entityManager->flush();

            $evaluations = $this->entityManager->getRepository(Evaluation::class)->findAll();
            foreach ($evaluations as $evaluation) {
                $this->entityManager->remove($evaluation);
            }
            $this->entityManager->flush();
            $categories = $this->entityManager->getRepository(CategorieService::class)->findAll();
            foreach ($categories as $categorie) {
                $this->entityManager->remove($categorie);
            }
            $this->entityManager->flush();
        }
        
        return $count;
    }
}
