<?php
// src/Service/ServicePublicManager.php

namespace App\Service;

use App\Dto\CreateServicePublicDto;
use App\Dto\UpdateServicePublicDto;
use App\Entity\CategorieService;
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
        
        // Valeurs par défaut
        $service->setStatut(StatutService::ACTIF);
        $service->setSourceDonnees('import_api');
    }

    private function hydraterEmail(ServicePublic $service, array $donnees): void
    {
        if (isset($donnees['email']) && filter_var($donnees['email'], FILTER_VALIDATE_EMAIL)) {
            $service->setEmail($donnees['email']);
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
            $service->setHorairesOuverture([
                'texte' => trim($donnees['horaires'])
            ]);
        }
    }

    private function hydraterCategorie(ServicePublic $service, array $donnees): void
    {
        if (!isset($donnees['type_service']) || empty($donnees['type_service'])) {
            return;
        }
        
        // Rechercher ou créer la catégorie
        $categorie = $this->categorieServiceRepository->findOneBy(['nom' => $donnees['type_service']]);
        
        if (!$categorie) {
            $categorie = new CategorieService();
            $categorie->setNom($donnees['type_service']);
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
        // Compter avant suppression
        $count = $this->repository->count([]);
        
        if ($count > 0) {
            $query = $this->entityManager->createQuery('DELETE FROM App\Entity\ServicePublic sp');
            $query->execute();
            $this->entityManager->flush();
        }
        
        return $count;
    }
}
