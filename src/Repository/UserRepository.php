<?php

namespace App\Repository;

use App\Entity\User;
use App\Dto\UserFilterDto;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findUsersWithFilters(UserFilterDto $filters): array
    {
        $qb = $this->createQueryBuilder('u');

        // Recherche globale
        if (!empty($filters->search)) {
            $qb->andWhere('u.email LIKE :search OR u.pseudo LIKE :search OR u.nom LIKE :search OR u.prenom LIKE :search')
            ->setParameter('search', '%' . $filters->search . '%');
        }
        
        // Filtre par rôle
        if ($filters->role) {
            $qb->andWhere('u.roles LIKE :role')
            ->setParameter('role', '%"' . $filters->role . '"%');
        }

        // Filtre par statut
        if ($filters->statut) {
            $qb->andWhere('u.statut = :statut')
            ->setParameter('statut', $filters->statut);
        }

        // Tri
        $validSortFields = ['id', 'email', 'pseudo', 'nom', 'prenom', 'createdAt', 'scoreFiabilite'];
        $sortField = in_array($filters->sortField, $validSortFields) ? $filters->sortField : 'id';
        $sortOrder = strtolower($filters->sortOrder) === 'desc' ? 'DESC' : 'ASC';
        
        $qb->orderBy('u.' . $sortField, $sortOrder);

        // Count total pour la pagination
        $countQb = clone $qb;
        $total = $countQb->select('COUNT(u.id)')->getQuery()->getSingleScalarResult();

        // Pagination
        $currentPage = max(1, $filters->page); // S'assurer que la page actuelle est au moins 1
        $limit = max(1, $filters->limit); // S'assurer que la limite est au moins 1

        $qb->setFirstResult(($currentPage - 1) * $limit)
        ->setMaxResults($limit);

        // Obtenir les résultats
        $users = $qb->getQuery()->getResult();

        return [
            'users' => $users,
            'total' => $total,
            'page' => $currentPage,
            'limit' => $limit,
        ];
    }


    /**
     * Trouve les utilisateurs par IDs
     */
    public function findByIds(array $ids): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les utilisateurs par statut
     */
    public function countByStatut(): array
    {
        $result = $this->createQueryBuilder('u')
            ->select('u.statut, COUNT(u.id) as count')
            ->groupBy('u.statut')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($result as $row) {
            $counts[$row['statut']->value] = $row['count'];
        }

        return $counts;
    }

        /**
     * Statistiques des utilisateurs
     *
     * @return array
     */
    public function getUserStats(): array
    {
        $qb = $this->createQueryBuilder('u');

        // Total des utilisateurs
        $total = $qb->select('COUNT(u.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // Utilisateurs actifs
        $qb = $this->createQueryBuilder('u');
        $active = $qb->select('COUNT(u.id)')
            ->where('u.statut = :statut')
            ->setParameter('statut', 'actif')
            ->getQuery()
            ->getSingleScalarResult();

        // Utilisateurs vérifiés
        $qb = $this->createQueryBuilder('u');
        $verified = $qb->select('COUNT(u.id)')
            ->where('u.isVerified = :verified')
            ->setParameter('verified', true)
            ->getQuery()
            ->getSingleScalarResult();

        // Nouveaux utilisateurs ce mois
        $qb = $this->createQueryBuilder('u');
        $thisMonth = $qb->select('COUNT(u.id)')
            ->where('u.createdAt >= :startOfMonth')
            ->setParameter('startOfMonth', new \DateTime('first day of this month'))
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total' => (int) $total,
            'active' => (int) $active,
            'verified' => (int) $verified,
            'thisMonth' => (int) $thisMonth,
            'inactiveRate' => $total > 0 ? round((($total - $active) / $total) * 100, 1) : 0
        ];
    }

    /**
     * Trouve les utilisateurs récents
     *
     * @param int $limit
     * @return User[]
     */
    public function findRecentUsers(int $limit = 10): array
    {
        return $this->createQueryBuilder('u')
            ->orderBy('u.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
