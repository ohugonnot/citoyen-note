<?php
// src/Repository/ServicePublicRepository.php

namespace App\Repository;

use App\Entity\CategorieService;
use App\Enum\StatutService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CategorieServiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CategorieService::class);
    }

    public function findAll(): array
    {
        return $this->createQueryBuilder('c')
            ->select('c')
            ->getQuery()
            ->getResult();
    }
}
