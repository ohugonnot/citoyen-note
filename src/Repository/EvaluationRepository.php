<?php

namespace App\Repository;

use App\Dto\EvaluationFilterDto;
use App\Entity\Evaluation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @extends ServiceEntityRepository<Evaluation>
 */
class EvaluationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Evaluation::class);
    }

    public function findPaginated(EvaluationFilterDto $filter): array
    {
        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.user', 'u')
            ->leftJoin('e.servicePublic', 's')
            ->addSelect('u', 's');

        if ($filter->search !== '') {
            $qb->andWhere('LOWER(e.commentaire) LIKE :search OR LOWER(s.nom) LIKE :search OR LOWER(u.pseudo) LIKE :search OR LOWER(u.email) LIKE :search')
                ->setParameter('search', '%' . strtolower($filter->search) . '%');
        }

        if ($filter->est_verifie !== null) {
            $qb->andWhere('e.estVerifie = :est_verifie')
                ->setParameter('est_verifie', $filter->est_verifie);
        }

        if ($filter->est_anonyme !== null) {
            $qb->andWhere('e.estAnonyme = :est_anonyme')
                ->setParameter('est_anonyme', $filter->est_anonyme);
        }

        if ($filter->statut !== null) {
            $qb->andWhere('e.statut = :statut')
                ->setParameter('statut', $filter->statut);
        }

        if ($filter->note_min !== null) {
            $qb->andWhere('e.note >= :note_min')
                ->setParameter('note_min', $filter->note_min);
        }

        if ($filter->note_max !== null) {
            $qb->andWhere('e.note <= :note_max')
                ->setParameter('note_max', $filter->note_max);
        }

        // CHANGEMENT ICI : utiliser l'ID au lieu de l'UUID
        if ($filter->service_id !== null) {
            $qb->andWhere('s.id = :service_id')
                ->setParameter('service_id', $filter->service_id);
        }

        // CHANGEMENT ICI aussi pour user_id
        if ($filter->user_id !== null) {
            if (is_numeric($filter->user_id)) {
                $qb->andWhere('u.id = :user_id')
                    ->setParameter('user_id', (int)$filter->user_id);
            } else {
                $qb->andWhere('u.uuid = :user_id')
                    ->setParameter('user_id', $filter->user_id);
            }
        }

        // Tri
        $sortField = match ($filter->sortField) {
            'note' => 'e.note',
            'createdAt' => 'e.createdAt',
            default => 'e.id'
        };

        $sortOrder = strtolower($filter->sortOrder) === 'asc' ? 'ASC' : 'DESC';
        $qb->orderBy($sortField, $sortOrder);

        // Pagination
        $offset = ($filter->page - 1) * $filter->limit;
        $qb->setFirstResult($offset)
           ->setMaxResults($filter->limit);

        $paginator = new Paginator($qb);

        // AJOUT DE DEBUG
        // $sql = $qb->getQuery()->getSQL();
        // dump($sql, $qb->getParameters()->toArray(), count($paginator));

        return [
            'total' => count($paginator),
            'items' => iterator_to_array($paginator),
        ];
    }
}
