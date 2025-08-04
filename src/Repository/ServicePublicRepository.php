<?php
// src/Repository/ServicePublicRepository.php

namespace App\Repository;

use App\Dto\ServicePublicFilterDto;
use App\Entity\ServicePublic;
use App\Entity\CategorieService;
use App\Enum\StatutService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ServicePublicRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ServicePublic::class);
    }

    public function findByProximite(float $lat, float $lng, float $rayon = 10): array
    {
        // Calcul approximatif pour la recherche par proximité
        $latRange = $rayon / 111; // 1 degré ≈ 111 km
        $lngRange = $rayon / (111 * cos(deg2rad($lat)));

        return $this->createQueryBuilder('sp')
            ->where('sp.latitude BETWEEN :latMin AND :latMax')
            ->andWhere('sp.longitude BETWEEN :lngMin AND :lngMax')
            ->andWhere('sp.statut = :statut')
            ->setParameter('latMin', $lat - $latRange)
            ->setParameter('latMax', $lat + $latRange)
            ->setParameter('lngMin', $lng - $lngRange)
            ->setParameter('lngMax', $lng + $lngRange)
            ->setParameter('statut', StatutService::ACTIF)
            ->orderBy('sp.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByCategorie(CategorieService $categorie): array
    {
        return $this->createQueryBuilder('sp')
            ->where('sp.categorie = :categorie')
            ->andWhere('sp.statut = :statut')
            ->setParameter('categorie', $categorie)
            ->setParameter('statut', StatutService::ACTIF)
            ->orderBy('sp.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByVille(string $ville): array
    {
        return $this->createQueryBuilder('sp')
            ->where('LOWER(sp.ville) LIKE LOWER(:ville)')
            ->andWhere('sp.statut = :statut')
            ->setParameter('ville', '%' . $ville . '%')
            ->setParameter('statut', StatutService::ACTIF)
            ->orderBy('sp.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function search(string $terme): array
    {
        return $this->createQueryBuilder('sp')
            ->leftJoin('sp.categorie', 'categorie')
            ->where('LOWER(sp.nom) LIKE LOWER(:terme)')
            ->orWhere('LOWER(sp.description) LIKE LOWER(:terme)')
            ->orWhere('LOWER(categorie.nom) LIKE LOWER(:terme)')
            ->andWhere('sp.statut = :statut')
            ->setParameter('terme', '%' . $terme . '%')
            ->setParameter('statut', StatutService::ACTIF)
            ->orderBy('sp.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findServicesWithFilters(ServicePublicFilterDto $filterDto): array
    {
        $qb = $this->createQueryBuilder('sp')
            ->leftJoin('sp.categorie', 'categorie');

        // Recherche
        if (!empty($filterDto->search)) {
            $expr = $qb->expr();
            $search = strtolower(trim($filterDto->search));
            $words = preg_split('/\s+/', $search);

            $fullSearchExpr = $expr->orX(
                $expr->like('LOWER(sp.nom)', ':fullSearch'),
                $expr->like('LOWER(sp.ville)', ':fullSearch'),
                $expr->like('LOWER(sp.description)', ':fullSearch'),
                $expr->like('LOWER(sp.codePostal)', ':fullSearch'),
                $expr->like('LOWER(categorie.nom)', ':fullSearch')
            );
            $qb->setParameter('fullSearch', '%' . $search . '%');

            $allWordsExpr = $expr->andX(); // ET entre les mots

            foreach ($words as $i => $word) {
                if (strlen($word) < 2) continue;
                $paramName = "word_$i";

                $wordExpr = $expr->orX(
                    $expr->like('LOWER(sp.nom)', ":$paramName"),
                    $expr->like('LOWER(sp.ville)', ":$paramName"),
                    $expr->like('LOWER(sp.description)', ":$paramName"),
                    $expr->like('LOWER(sp.codePostal)', ":$paramName"),
                    $expr->like('LOWER(categorie.nom)', ":$paramName")
                );

                $allWordsExpr->add($wordExpr);
                $qb->setParameter($paramName, '%' . $word . '%');
            }

            // Final : fullSearch OR (every word is found)
            $qb->andWhere($expr->orX($fullSearchExpr, $allWordsExpr));
        }

        // Filtres
        if ($filterDto->statut) {
            $qb->andWhere('sp.statut = :statut')
            ->setParameter('statut', $filterDto->statut);
        }

        if ($filterDto->ville) {
            $qb->andWhere('sp.ville = :ville')
            ->setParameter('ville', $filterDto->ville);
        }

        if ($filterDto->categorie) {
            $qb->andWhere('categorie.id = :categorie')
            ->setParameter('categorie', $filterDto->categorie, is_string($filterDto->categorie) ? 'uuid' : null);
        }

        // 🎯 Tri avec mapping des champs
        $orderBy = $this->mapSortField($filterDto->sortField);
        $qb->orderBy($orderBy, $filterDto->sortOrder);

        // Pagination
        $totalQuery = clone $qb;
        $total = $totalQuery->select('COUNT(sp.id)')->getQuery()->getSingleScalarResult();

        $services = $qb
            ->setFirstResult(($filterDto->page - 1) * $filterDto->limit)
            ->setMaxResults($filterDto->limit)
            ->getQuery()
            ->getResult();

        return [
            'services' => $services, 
            'total' => $total
        ];
    }

    private function mapSortField(string $sortField): string
    {
        return match($sortField) {
            'categorie.nom', 'categorie_nom' => 'categorie.nom',
            'nom', 'ville', 'statut', 'createdAt', 'updatedAt' => 'sp.' . $sortField,
            default => 'sp.' . $sortField
        };
    }

    public function getServiceStats(): array
    {
        $dql = "SELECT 
                    COUNT(sp) as total,
                    SUM(CASE WHEN sp.statut = 'actif' THEN 1 ELSE 0 END) as actifs,
                    SUM(CASE WHEN sp.statut = 'ferme' THEN 1 ELSE 0 END) as fermes,
                    COUNT(DISTINCT sp.ville) as villes
                FROM App\Entity\ServicePublic sp";
                
        return $this->getEntityManager()
            ->createQuery($dql)
            ->getSingleResult();
    }

    public function findRecentServices(int $limit): array
    {
        return $this->createQueryBuilder('sp')
            ->orderBy('sp.dateCreation', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findAllCategories(): array
    {
        return $this->getEntityManager()
            ->getRepository(\App\Entity\CategorieService::class)
            ->createQueryBuilder('categorie')
            ->select('categorie.nom')
            ->orderBy('categorie.nom', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }
}
