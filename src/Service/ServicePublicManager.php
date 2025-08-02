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
    // Liste complète des catégories possibles (normalisées)
    private array $allCategories = [
        'Mairie','Préfecture','Sous Préfecture','Hôpital','CAF','Pôle Emploi','France Travail',
        'CPAM','URSSAF','Tribunal','Conseil Départemental','Métropole','EPCI','Centre Des Impôts',
        'Trésorerie','MDPH','Police','Police Municipale','Commissariat Police','Gendarmerie',
        'Éducation','Université','École','Bibliothèque','Archives','Permanence Juridique','Droit Travail',
        'France Services','DRVA','PMI','Cap Emploi','DAC','DD FIP','BDF','SAMU','ARACT','PIF','Point Justice',
        'Gendarmerie Moto','CIJ','Conciliateur Fiscal','CIDF','TGI','PCB','Maison Arrêt','Mission Locale',
        'Accompagnement Personnes Âgées','DDPJJ','ARS','SIP','FDAPP','FR Renov','VIF TJ','AAV','Centre Impôts Fonciers',
        'DRAF','CRIB','CIO','MAIA','CCI','BAV','CANOPE Atelier','Maison Handicapées','AD','GRETA','CDAD','DREAL UT',
        'Chambre Notaires','Crédit Municipal','Chambre Métier','CLIC','Maison Métropole Lyon','Bureau Douane','CICAS',
        'CG','TI','MSA','DDCSPP','INPI','DRARI','SDAC','MJD','ARS Antenne','ONAC','Point Accueil Numérique','SDIS',
        'CIRFA','PRS','ANAH','Inspection Académique','SDJES','Préfecture Greffe Associations','TE','Ordre Avocats','DDVA',
        'CIRGN','Chambre Agriculture','SGAMI','CROUS','DR Femmes','DR France Travail','Paierie Régionale','BER','Hypothèque',
        'FDC','AFPA','Laboratoire Départemental','DDPN DIPN','URCAUE','CARSAT','CAUE','CTRC','Chambre Métier Régionale',
        'Maison Emploi','CEREMA','DD Femmes','APEC','CDG','Prud\'hommes','APECITA','CNFPT','Tribunal Commerce','Onisep','EPIDE',
        'Plateforme Naturalisation','Huissiers Justice','CNRS','DIR Mer','SSTI','Safer','BSN','DREAL','SIE','CARIF OREF',
        'DR INSEE','Gendarmerie Montagne','CAA','Commission Conciliation','ADIL','Parc Naturel Régional','VIF CA','SUIO','TA',
        'DIR PJ','DRDDI','SDE','DDETS','CHU','SPIP','DRAC','Centre Détention','Gendarmerie Départementale','Rectorat','DRFIP',
        'DIRECCTE','Paierie Départementale','CESR','Maison Centrale','DRPJJ','OFII','Cour Appel','DZ PAF','CANOPE DT','DIR Météo',
        'DRIEAT','DRAJES','Centre Pénitentiaire','CREPS','Préfecture Régionale','ONF','DML','ADEME','ESPE','DCF','TAE','DDPP','CR',
        'DRIEAT UT','RRC','EMZPN','CRPV','CRFPN','DZPN','Médiateur France Travail','Did Routes','AGEFIPH','DRIHL UT','CRC',
        'Service Navigation','CSL','ESM','CNRA','PP Marseille','DRSP','DRIHL','ASN','Paris PPP','SZ RT','Conseil Culture',
        'Paris PPP Gesvres','SZ PJ'
    ];

    // Liste des acronymes à conserver en majuscules
    private array $acronyms = [
        'caf','urssaf','cpam','sip','mdph','cij','tgi','cci','msa','ars','ofii','anah','inpi','epci','cicas','csl',
        'ta','apec','apecita','cnfpt','cnrs','crous','cerema','carif','oref','agefiph','safer','sdis','drac','dreal',
        'dreets','draf','drfip','drfemmes','drsp','drari','drddi','ddpp','ddcspp','ddeuts','ddpn','ddpjj','dmd','dac','ddt'
    ];

    private array $iconesMapping;
    private array $couleursMapping;
    private array $categoriesCache = [];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private ServicePublicRepository $repository,
        private CategorieServiceRepository $categorieServiceRepository
    ) {
        // Initialisation des mappings par défaut
        $defaultIcon = 'bi-building';
        $defaultColor = '#6c757d';
        $this->iconesMapping = array_fill_keys($this->allCategories, $defaultIcon);
        $this->couleursMapping = array_fill_keys($this->allCategories, $defaultColor);

        // Surcharges spécifiques
        $overIcon = [
            'Mairie'=>'bi-building','Préfecture'=>'bi-building-check','Sous Préfecture'=>'bi-building-gear',
            'Hôpital'=>'bi-hospital','CAF'=>'bi-people-fill','Pôle Emploi'=>'bi-briefcase','CPAM'=>'bi-heart-pulse',
            'URSSAF'=>'bi-calculator','Tribunal'=>'bi-scale','Conseil Départemental'=>'bi-bank',
            'Métropole'=>'bi-buildings','Éducation'=>'bi-mortarboard','Université'=>'bi-book','École'=>'bi-backpack',
            'Bibliothèque'=>'bi-journal-bookmark','Archives'=>'bi-archive','Mission Locale'=>'bi-people',
            'Safer'=>'bi-house','Point Justice'=>'bi-scale','Cerema'=>'bi-diagram-3','Conciliateur Fiscal'=>'bi-calculator'
        ];
        foreach ($overIcon as $cat=>$icon) {
            $this->iconesMapping[$cat] = $icon;
        }

$overColor = [
    'Mairie'=>'#0d6efd','Préfecture'=>'#6610f2','Sous Préfecture'=>'#6f42c1','Hôpital'=>'#dc3545',
    'CAF'=>'#20c997','Pôle Emploi'=>'#fd7e14','CPAM'=>'#e83e8c','URSSAF'=>'#6c757d','Tribunal'=>'#495057',
    'Conseil Départemental'=>'#198754','Métropole'=>'#0dcaf0','Éducation'=>'#0a58ca','Université'=>'#6f42c1',
    'École'=>'#0dcaf0','Bibliothèque'=>'#6610f2','Archives'=>'#495057','Mission Locale'=>'#28a745',
    'Safer'=>'#8b5cf6','Point Justice'=>'#374151','Cerema'=>'#dc2626','Conciliateur Fiscal'=>'#ea580c'
];
foreach ($overColor as $cat=>$col) {
    $this->couleursMapping[$cat] = $col;
}
}

/**
 * Normalise le nom de catégorie : capitalise chaque mot sauf acronymes
 */
private function normalizeCategoryName(string $raw): string
{
    $name = str_replace(['_','-'], ' ', strtolower(trim($raw)));
    $words = explode(' ', $name);
    foreach ($words as &$word) {
        if (in_array($word, $this->acronyms, true)) {
            $word = strtoupper($word);
        } else {
            $word = ucfirst($word);
        }
    }
    return implode(' ', $words);
}

/**
 * Associe ou crée une catégorie avec icône et couleur
 */
private function hydraterCategorieOptimisee(ServicePublic $service, array $donnees): void
{
    $rawName = trim($donnees['type_service'] ?? '');
    if (!$rawName) {
        return;
    }

    $normalized = $this->normalizeCategoryName($rawName);
    $cacheKey = 'nom_' . $normalized;

    if (isset($this->categoriesCache[$cacheKey])) {
        $service->setCategorie($this->categoriesCache[$cacheKey]);
        return;
    }

    $categorie = $this->categorieServiceRepository->findOneBy(['nom' => $normalized]);
    if (!$categorie) {
        $categorie = new CategorieService();
        $categorie->setNom($normalized);
        $categorie->setIcone($this->iconesMapping[$normalized] ?? 'bi-building');
        $categorie->setCouleur($this->couleursMapping[$normalized] ?? '#6c757d');
        $this->entityManager->persist($categorie);
    }

    $this->categoriesCache[$cacheKey] = $categorie;
    $service->setCategorie($categorie);
}

public function creer(array $donnees, bool $flush = true): ServicePublic
{
    $service = new ServicePublic();
    $this->hydraterDepuisDonnees($service, $donnees);
    $this->entityManager->persist($service);
    if ($flush) {
        $this->entityManager->flush();
    }
    return $service;
}

public function mettreAJour(ServicePublic $service, array $donnees, bool $flush = true): ServicePublic
{
    $this->hydraterDepuisDonnees($service, $donnees);
    if ($flush) {
        $this->entityManager->flush();
    }
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
    usort($services, function($a, $b) use ($lat, $lng) {
        $distanceA = $a->getDistanceDepuis($lat, $lng) ?? PHP_FLOAT_MAX;
        $distanceB = $b->getDistanceDepuis($lat, $lng) ?? PHP_FLOAT_MAX;
        return $distanceA <=> $distanceB;
    });
    return $services;
}

private function hydraterDepuisDonnees(ServicePublic $service, array $donnees): void
{
    if (isset($donnees['id_gouv']) && !empty($donnees['id_gouv'])) {
        $service->setIdGouv($donnees['id_gouv']);
    }

    if(empty($donnees["code_postal"])) {
        $donnees["code_postal"] = '000000';
    }
    $mappingChamps = [
        'nom' => 'setNom',
        'adresse' => 'setAdresseComplete',
        'code_postal' => 'setCodePostal',
        'ville' => 'setVille',
        'telephone' => 'setTelephone',
        'description' => 'setDescription',
        'site_internet' => 'setSiteWeb',
    ];
    foreach ($mappingChamps as $cle => $setter) {
        if (isset($donnees[$cle]) && !empty($donnees[$cle])) {
            $service->$setter($donnees[$cle]);
        }
    }
    $this->hydraterEmail($service, $donnees);
    $this->hydraterCoordonnees($service, $donnees);
    $this->hydraterHoraires($service, $donnees);
    $this->hydraterStatut($service, $donnees);
    $this->hydraterCategorieOptimisee($service, $donnees);
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
        } catch (\ValueError) {
            $service->setStatut(StatutService::ACTIF);
        }
    } else {
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
        $service->setHorairesOuverture($donnees['horaires']);
    }
}

public function sauvegarder(array $donnees, bool $flush = true): ServicePublic
{
    $serviceExistant = null;
    if (!empty($donnees['id_gouv'])) {
        $serviceExistant = $this->repository->findOneBy(['idGouv' => $donnees['id_gouv']]);
    }
    if ($serviceExistant) {
        return $this->mettreAJour($serviceExistant, $donnees, $flush);
    }
    return $this->creer($donnees, $flush);
}

public function viderCacheCategories(): void
{
    $this->categoriesCache = [];
}

public function sauvegarderLot(array $servicesDonnees): array
{
    $stats = ['succes' => 0, 'erreurs' => 0, 'erreurs_detail' => []];
    $this->entityManager->beginTransaction();
    foreach ($servicesDonnees as $index => $donnees) {
        try {
            $this->sauvegarder($donnees, false);
            $stats['succes']++;
        } catch (\Exception $e) {
            $stats['erreurs']++;
            $stats['erreurs_detail'][] = ['index'=>$index,'service_id'=>$donnees['id_gouv']??'unknown','erreur'=>$e->getMessage()];
        }
    }
    $this->entityManager->flush();
    $this->entityManager->commit();
    return $stats;
}

public function nettoyerContexte(): void
{
    $this->viderCacheCategories();
    $this->entityManager->clear();
    gc_collect_cycles();
}

public function getStatsMemoire(): array
{
    return [
        'categories_cache_count' => count($this->categoriesCache),
        'memory_usage' => memory_get_usage(true),
        'memory_peak' => memory_get_peak_usage(true),
        'entity_manager_open' => $this->entityManager->isOpen()
    ];
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
        } catch (\Exception) {
            continue;
        }
    }
    if ($count) {
        $this->entityManager->flush();
    }
    return $count;
}

public function importerDepuisCsv(string $cheminFichier, bool $viderAvant = true): array
{
    $resultats = ['succes'=>0,'erreurs'=>[], 'supprimes'=>0];
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
                $resultats['erreurs'][] = ['ligne'=>$data,'erreur'=>$e->getMessage()];
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
    foreach ($services as $service) {
        $this->entityManager->remove($service);
    }
    $this->entityManager->flush();
    $evaluations = $this->entityManager->getRepository(Evaluation::class)->findAll();
    foreach ($evaluations as $eval) {
        $this->entityManager->remove($eval);
    }
    $this->entityManager->flush();
    $categories = $this->entityManager->getRepository(CategorieService::class)->findAll();
    foreach ($categories as $cat) {
        $this->entityManager->remove($cat);
    }
    $this->entityManager->flush();
    return $count;
}
}
