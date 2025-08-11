<?php
// src/Repository/ServicePublicRepository.php

namespace App\Repository;

use App\Dto\ServicePublicFilterDto;
use App\Entity\ServicePublic;
use App\Entity\CategorieService;
use App\Enum\StatutService;
use App\Service\SearchNormalizer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;

class ServicePublicRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ServicePublic::class);
    }

    private function driver(): string
    {
        $platform = $this->getEntityManager()
            ->getConnection()
            ->getDatabasePlatform();

        return match (true) {
            $platform instanceof PostgreSQLPlatform => 'postgresql',
            $platform instanceof MySQLPlatform      => 'mysql',
            $platform instanceof SqlitePlatform     => 'sqlite',
            default                                 => 'unknown',
        };
    }

    private function colExpr(string $column): string
    {
        $platform = $this->driver(); // "postgresql" | "mysql" | "sqlite" | ...
        return match ($platform) {
            'postgresql' => "LOWER(unaccent($column))",
            default      => "LOWER($column)", // SQLite & autres: fallback (accents non retirés)
        };
    }

    public function findByProximite(float $lat, float $lng, float $rayon = 10): array
    {
        $latRange = $rayon / 111;
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
        $qb = $this->createQueryBuilder('sp')
            ->andWhere('sp.statut = :statut')
            ->setParameter('statut', StatutService::ACTIF)
            ->orderBy('sp.nom', 'ASC');

        // accent-insensitive
        $expr = $this->colExpr('sp.ville') . ' LIKE :ville';
        $qb->andWhere($expr)->setParameter('ville', '%' . SearchNormalizer::normalize($ville) . '%');

        return $qb->getQuery()->getResult();
    }

    public function search(string $terme): array
    {
        $qb = $this->createQueryBuilder('sp')
            ->leftJoin('sp.categorie', 'categorie')
            ->andWhere('sp.statut = :statut')
            ->setParameter('statut', StatutService::ACTIF)
            ->orderBy('sp.nom', 'ASC');

        $t = SearchNormalizer::normalize($terme);
        $cols = [
            $this->colExpr('sp.nom'),
            $this->colExpr('sp.description'),
            $this->colExpr('categorie.nom'),
        ];
        $or = '(' . implode(' OR ', array_map(fn($c) => "$c LIKE :t", $cols)) . ')';

        $qb->andWhere($or)->setParameter('t', '%' . $t . '%');

        return $qb->getQuery()->getResult();
    }

    // === Méthode principale paginée avec filtres + recherche robuste =========
    /**
     * Recherche paginée avec :
     *  - accent/case insensitive
     *  - (full string) OR (tous les mots, AND entre mots / OR entre colonnes)
     *  - filtres statut/ville/catégorie
     *  - optionnel: filtre par distance et tri distance si coords fournies
     *
     * @return array{services: array, total: int}
     */
    public function findServicesWithFilters(
        ServicePublicFilterDto $filterDto,
        ?float $lat = null,
        ?float $lng = null,
        ?float $rayon = null,
        bool $useGeoSql = true
    ): array {
        $qb = $this->createQueryBuilder('sp')
            ->leftJoin('sp.categorie', 'categorie')
            ->andWhere('sp.statut = :statut')
            ->setParameter('statut', $filterDto->statut ?? StatutService::ACTIF);

        // ---- GEO (rayon en km) ----
        if ($lat !== null && $lng !== null && $useGeoSql) {

            $qb->andWhere(sprintf(
                '(6371 * acos(cos(radians(%f)) * cos(radians(sp.latitude)) * cos(radians(sp.longitude) - radians(%f)) + sin(radians(%f)) * sin(radians(sp.latitude)))) <= :rayon',
                $lat, $lng, $lat
            ))
                ->andWhere('sp.latitude IS NOT NULL AND sp.longitude IS NOT NULL')
                ->setParameter('rayon', $rayon ?? 25);

            // distance calculée côté SQL si besoin de trier
            if ($filterDto->sortField === 'distance') {
                $qb->addSelect(sprintf(
                    '(6371 * acos(cos(radians(%f)) * cos(radians(sp.latitude)) * cos(radians(sp.longitude) - radians(%f)) + sin(radians(%f)) * sin(radians(sp.latitude)))) AS HIDDEN distance',
                    $lat, $lng, $lat
                ));
            }
        } elseif (!empty($filterDto->ville)) {
            // filtre ville (accent-insensitive)
            $qb->andWhere($this->colExpr('sp.ville') . ' LIKE :ville')
               ->setParameter('ville', '%' . SearchNormalizer::normalize($filterDto->ville) . '%');
        }

        if (!empty($filterDto->categorie)) {
            $qb->andWhere('categorie.id = :categorie')
               ->setParameter('categorie', $filterDto->categorie, is_string($filterDto->categorie) ? 'uuid' : null);
        }

        if (!empty($filterDto->source)) {
            $qb->andWhere('sp.source = :source')->setParameter('source', $filterDto->source);
        }

        // ---- RECHERCHE : (full string) OR (AND entre mots, OR entre colonnes) ----
        if (!empty($filterDto->search)) {
            $columns = [
                $this->colExpr('sp.nom'),
                $this->colExpr('sp.ville'),
                $this->colExpr('sp.description'),
                $this->colExpr('sp.codePostal'),
                $this->colExpr('categorie.nom'),
            ];

            // full string
            $full = SearchNormalizer::normalize($filterDto->search);
            $fullOr = [];
            foreach ($columns as $i => $c) {
                $fullOr[] = "$c LIKE :fullSearch";
            }
            $qb->setParameter('fullSearch', '%' . $full . '%');

            // words AND (each word must match at least one column)
            $words = array_filter(array_map([SearchNormalizer::class, 'normalize'], $filterDto->words()));
            $andParts = [];
            foreach ($words as $k => $w) {
                $param = "w_$k";
                $likes = [];
                foreach ($columns as $c) {
                    $likes[] = "$c LIKE :$param";
                }
                $andParts[] = '(' . implode(' OR ', $likes) . ')';
                $qb->setParameter($param, '%' . $w . '%');
            }

            $fullClause = '(' . implode(' OR ', $fullOr) . ')';
            $andClause  = $andParts ? implode(' AND ', $andParts) : '1=1';

            $qb->andWhere("($fullClause OR ($andClause))");
        }

        $orderBy = $this->mapSortField($filterDto->sortField);
        if ($filterDto->sortField === 'distance' && $lat !== null && $lng !== null && $useGeoSql) {
            $qb->orderBy('distance', strtoupper($filterDto->sortOrder) === 'DESC' ? 'DESC' : 'ASC');
        } else {
            $qb->orderBy($orderBy, strtoupper($filterDto->sortOrder) === 'DESC' ? 'DESC' : 'ASC');
        }

        $totalQuery = clone $qb;
        $total = (int) $totalQuery->select('COUNT(sp.id)')->resetDQLPart('orderBy')->getQuery()->getSingleScalarResult();

        // ---- Pagination ----
        $services = $qb
            ->setFirstResult(max(0, ($filterDto->page - 1) * $filterDto->limit))
            ->setMaxResults($filterDto->limit)
            ->getQuery()
            ->getResult();

        return [
            'services' => $services,
            'total' => $total,
        ];
    }

    private function mapSortField(string $sortField): string
    {
        return match ($sortField) {
            'distance' => 'sp.nom', 
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
