<?php

namespace App\Tests\Unit\Service;

use App\Entity\CategorieService;
use App\Repository\CategorieServiceRepository;
use App\Service\CategorieServiceManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CategorieServiceManagerTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private CategorieServiceRepository&MockObject $repository;
    private CategorieServiceManager $manager;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(CategorieServiceRepository::class);
        $this->manager = new CategorieServiceManager($this->em, $this->repository);
    }

    public function testFindAllActiveFiltersOnlyActive(): void
    {
        $cat1 = new CategorieService();
        $cat1->setNom('Active');

        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['actif' => true])
            ->willReturn([$cat1]);

        $result = $this->manager->findAllActive();
        $this->assertCount(1, $result);
    }

    public function testDeleteSoftDeletes(): void
    {
        $cat = new CategorieService();
        $cat->setNom('Test');
        $cat->setActif(true);

        $this->em->expects($this->once())->method('flush');
        $this->em->expects($this->never())->method('remove');

        $this->manager->delete($cat);
        $this->assertFalse($cat->isActif());
    }

    public function testHardDeleteRemovesEntity(): void
    {
        $cat = new CategorieService();
        $cat->setNom('Test');

        $this->em->expects($this->once())->method('remove')->with($cat);
        $this->em->expects($this->once())->method('flush');

        $this->manager->hardDelete($cat);
    }

    public function testCreatePersistsAndFlushes(): void
    {
        $cat = new CategorieService();
        $cat->setNom('Nouveau');

        $this->em->expects($this->once())->method('persist')->with($cat);
        $this->em->expects($this->once())->method('flush');

        $result = $this->manager->create($cat);
        $this->assertSame($cat, $result);
    }

    public function testUpdateFlushes(): void
    {
        $cat = new CategorieService();
        $cat->setNom('Existant');

        $this->em->expects($this->once())->method('flush');
        $this->em->expects($this->never())->method('persist');

        $result = $this->manager->update($cat);
        $this->assertSame($cat, $result);
    }
}
