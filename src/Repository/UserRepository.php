<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Trouve les utilisateurs avec filtres, recherche et pagination
     *
     * @param array $filters
     * @return array
     */
    public function findUsersWithFilters(array $filters): array
    {
        $qb = $this->createQueryBuilder('u');

        // Recherche textuelle
        if (!empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('u.email', ':search'),
                    $qb->expr()->like('u.pseudo', ':search'),
                    $qb->expr()->like('u.nom', ':search'),
                    $qb->expr()->like('u.prenom', ':search'),
                    $qb->expr()->like(
                        $qb->expr()->concat('u.nom', $qb->expr()->concat($qb->expr()->literal(' '), 'u.prenom')),
                        ':search'
                    )
                )
            )->setParameter('search', $searchTerm);
        }

        // Filtre par rôle
        if (!empty($filters['role'])) {
            $qb->andWhere('u.roles LIKE :role')
                ->setParameter('role', '%"' . $filters['role'] . '"%');
        }

        // Filtre par statut
        if (!empty($filters['statut'])) {
            $qb->andWhere('u.statut = :statut')
                ->setParameter('statut', $filters['statut']);
        }

        // Tri
        $sortField = $filters['sortField'] ?? 'id';
        $sortOrder = strtoupper($filters['sortOrder'] ?? 'ASC');

        $allowedSortFields = ['id', 'email', 'pseudo', 'nom', 'prenom', 'createdAt', 'scoreFiabilite'];
        if (in_array($sortField, $allowedSortFields)) {
            $qb->orderBy('u.' . $sortField, $sortOrder);
        }

        // Compter le total avant pagination
        $totalQb = clone $qb;
        $total = $totalQb->select('COUNT(u.id)')->getQuery()->getSingleScalarResult();

        // Pagination
        $page = max(1, $filters['page'] ?? 1);
        $limit = max(1, min(50, $filters['limit'] ?? 10));
        $offset = ($page - 1) * $limit;

        $qb->setFirstResult($offset)
            ->setMaxResults($limit);

        $users = $qb->getQuery()->getResult();

        return [
            'users' => $users,
            'total' => (int) $total
        ];
    }

    /**
     * Recherche d'utilisateurs par terme de recherche simple
     *
     * @param string $searchTerm
     * @param int $limit
     * @return User[]
     */
    public function searchUsers(string $searchTerm, int $limit = 20): array
    {
        $searchTerm = '%' . $searchTerm . '%';

        return $this->createQueryBuilder('u')
            ->where('u.email LIKE :search OR u.pseudo LIKE :search OR u.nom LIKE :search OR u.prenom LIKE :search')
            ->setParameter('search', $searchTerm)
            ->orderBy('u.pseudo', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les utilisateurs par rôle
     *
     * @param string $role
     * @return User[]
     */
    public function findByRole(string $role): array
    {
        return $this->createQueryBuilder('u')
            ->where('JSON_CONTAINS(u.roles, :role) = 1')
            ->setParameter('role', json_encode($role))
            ->orderBy('u.pseudo', 'ASC')
            ->getQuery()
            ->getResult();
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