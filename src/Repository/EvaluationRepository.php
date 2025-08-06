<?php

namespace App\Repository;

use App\Dto\EvaluationFilterDto;
use App\Entity\Evaluation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class EvaluationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Evaluation::class);
    }

    public function findPaginated(EvaluationFilterDto $filterDto): array
    {
        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.user', 'u')
            ->leftJoin('e.servicePublic', 's')
            ->addSelect('u', 's');

        $this->applyFilters($qb, $filterDto);

        // Compter le total
        $totalQb = clone $qb;
        $total = $totalQb
            ->select('COUNT(DISTINCT e.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // Appliquer pagination et tri
        $this->applySortingAndPagination($qb, $filterDto);

        $items = $qb->getQuery()->getResult();

        return [
            'items' => $items,
            'total' => (int) $total,
        ];
    }

    private function applyFilters(QueryBuilder $qb, EvaluationFilterDto $filterDto): void
    {
        // Recherche multi-mots
        if ($filterDto->hasSearch()) {
            $this->applyMultiWordSearch($qb, $filterDto);
        }

        // Filtre par statut de vérification
        if ($filterDto->est_verifie !== null) {
            $qb->andWhere('e.estVerifie = :estVerifie')
               ->setParameter('estVerifie', $filterDto->est_verifie);
        }

        // Filtre anonyme
        if ($filterDto->est_anonyme !== null) {
            $qb->andWhere('e.estAnonyme = :estAnonyme')
               ->setParameter('estAnonyme', $filterDto->est_anonyme);
        }

        // Filtre par note min/max
        if ($filterDto->note_min !== null) {
            $qb->andWhere('e.note >= :noteMin')
               ->setParameter('noteMin', $filterDto->note_min);
        }

        if ($filterDto->note_max !== null) {
            $qb->andWhere('e.note <= :noteMax')
               ->setParameter('noteMax', $filterDto->note_max);
        }

        // Filtre par service
        if ($filterDto->service_id !== null) {
            $qb->andWhere('s.id = :serviceId')
               ->setParameter('serviceId', $filterDto->service_id);
        }

        // Filtre par utilisateur
        if ($filterDto->user_id !== null) {
            $qb->andWhere('u.id = :userId')
               ->setParameter('userId', $filterDto->user_id);
        }
    }

    /**
     * Applique une recherche multi-mots intelligente
     * Chaque mot doit être trouvé dans au moins un des champs recherchables
     */
    private function applyMultiWordSearch(QueryBuilder $qb, EvaluationFilterDto $filterDto): void
    {
        $searchTerms = $filterDto->searchTerms;
        
        if (empty($searchTerms)) {
            return;
        }

        $orConditions = [];
        
        foreach ($searchTerms as $index => $term) {
            $paramName = 'searchTerm' . $index;
            $termPattern = '%' . $term . '%';
            
            // Pour chaque terme, créer une condition OR sur tous les champs
            $termConditions = [
                "LOWER(e.commentaire) LIKE LOWER(:$paramName)",
                "LOWER(e.pseudo) LIKE LOWER(:$paramName)",
                "LOWER(u.pseudo) LIKE LOWER(:$paramName)",
                "LOWER(u.email) LIKE LOWER(:$paramName)",
                "LOWER(u.nom) LIKE LOWER(:$paramName)",
                "LOWER(u.prenom) LIKE LOWER(:$paramName)",
                "LOWER(s.nom) LIKE LOWER(:$paramName)",
                "LOWER(s.description) LIKE LOWER(:$paramName)",
            ];
            
            // Joindre avec OR pour ce terme spécifique
            $orConditions[] = '(' . implode(' OR ', $termConditions) . ')';
            $qb->setParameter($paramName, $termPattern);
        }
        
        // Tous les termes doivent être trouvés (AND entre les groupes de conditions)
        $qb->andWhere('(' . implode(') AND (', $orConditions) . ')');
    }

    private function applySortingAndPagination(QueryBuilder $qb, EvaluationFilterDto $filterDto): void
    {
        // Tri
        $sortField = $this->mapSortField($filterDto->sortField);
        $sortDirection = strtoupper($filterDto->sortOrder) === 'ASC' ? 'ASC' : 'DESC';
        
        $qb->orderBy($sortField, $sortDirection);

        // Tri secondaire par ID pour assurer la cohérence
        if ($sortField !== 'e.id') {
            $qb->addOrderBy('e.id', 'DESC');
        }

        // Pagination
        $offset = ($filterDto->page - 1) * $filterDto->limit;
        $qb->setFirstResult($offset)
           ->setMaxResults($filterDto->limit);
    }

    private function mapSortField(string $field): string
    {
        return match ($field) {
            'user.nom', 'user' => 'u.nom',
            'user.email' => 'u.email',
            'service.nom', 'service' => 's.nom',
            'note' => 'e.note',
            'commentaire' => 'e.commentaire',
            'createdAt' => 'e.createdAt',
            'updatedAt' => 'e.updatedAt',
            'est_verifie' => 'e.estVerifie',
            'est_anonyme' => 'e.estAnonyme',
            default => 'e.createdAt',
        };
    }

    public function findByUuids(array $uuids): array
    {
        if (empty($uuids)) {
            return [];
        }

        return $this->createQueryBuilder('e')
            ->where('e.uuid IN (:uuids)')
            ->setParameter('uuids', $uuids)
            ->getQuery()
            ->getResult();
    }

    public function save(Evaluation $evaluation): void
    {
        $this->getEntityManager()->persist($evaluation);
        $this->getEntityManager()->flush();
    }

    public function remove(Evaluation $evaluation): void
    {
        $this->getEntityManager()->remove($evaluation);
        $this->getEntityManager()->flush();
    }
}