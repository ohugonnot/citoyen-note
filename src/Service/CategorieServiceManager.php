<?php

namespace App\Service;

use App\Entity\CategorieService;
use App\Repository\CategorieServiceRepository;
use Doctrine\ORM\EntityManagerInterface;

class CategorieServiceManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CategorieServiceRepository $repository
    ) {
    }

    public function findAllActive(): array
    {
        return $this->repository->findBy(
            ['actif' => true],
            ['ordreAffichage' => 'ASC', 'nom' => 'ASC']
        );
    }

    public function findById(string $id): ?CategorieService
    {
        return $this->repository->find($id);
    }

    public function findActiveById(string $id): ?CategorieService
    {
        return $this->repository->findOneBy([
            'id' => $id,
            'actif' => true
        ]);
    }

    public function create(CategorieService $categorie): CategorieService
    {
        $this->entityManager->persist($categorie);
        $this->entityManager->flush();

        return $categorie;
    }

    public function update(CategorieService $categorie): CategorieService
    {
        $this->entityManager->flush();

        return $categorie;
    }

    public function delete(CategorieService $categorie): void
    {
        $categorie->setActif(false);
        $this->entityManager->flush();
    }

    public function hardDelete(CategorieService $categorie): void
    {
        $this->entityManager->remove($categorie);
        $this->entityManager->flush();
    }
}
