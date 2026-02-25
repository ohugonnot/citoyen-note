<?php

namespace App\Tests\Unit\Entity;

use App\Entity\ServicePublic;
use App\Enum\StatutService;
use PHPUnit\Framework\TestCase;

class ServicePublicTest extends TestCase
{
    private ServicePublic $service;

    protected function setUp(): void
    {
        $this->service = new ServicePublic();
        $this->service->setNom('Mairie de Test');
        $this->service->setCodePostal('75001');
        $this->service->setVille('Paris');
    }

    public function testIdIsUuid(): void
    {
        $this->assertNotNull($this->service->getId());
    }

    public function testGetDistanceFromReturnsNullWithoutCoordinates(): void
    {
        $this->assertNull($this->service->getDistanceFrom(48.0, 2.0));
    }

    public function testGetDistanceFromCalculatesCorrectly(): void
    {
        $this->service->setLatitude(48.8566);
        $this->service->setLongitude(2.3522);

        $distance = $this->service->getDistanceFrom(45.7640, 4.8357);
        $this->assertNotNull($distance);
        $this->assertEqualsWithDelta(392, $distance, 10);
    }

    public function testHasCoordonnees(): void
    {
        $this->assertFalse($this->service->hasCoordonnees());

        $this->service->setLatitude(48.0);
        $this->service->setLongitude(2.0);
        $this->assertTrue($this->service->hasCoordonnees());
    }

    public function testEstOuvert(): void
    {
        // Default statut is ACTIF
        $this->assertTrue($this->service->estOuvert());

        $this->service->setStatut(StatutService::FERME);
        $this->assertFalse($this->service->estOuvert());
    }

    public function testGetNoteMoyenneEmptyReturnsNull(): void
    {
        $this->assertNull($this->service->getNoteMoyenne());
    }

    public function testGetNombreEvaluationsEmpty(): void
    {
        $this->assertSame(0, $this->service->getNombreEvaluations());
    }

    public function testGetAdresseFormateeWithAdresseComplete(): void
    {
        $this->service->setAdresseComplete('1 rue de la Paix');
        $this->assertSame('1 rue de la Paix 75001 Paris', $this->service->getAdresseFormatee());
    }

    public function testGetAdresseFormateeWithoutAdresseComplete(): void
    {
        // No adresseComplete set: result is trimmed "codePostal ville"
        $this->assertSame('75001 Paris', $this->service->getAdresseFormatee());
    }

    public function testGenerateSlugCreatesSlug(): void
    {
        $this->service->generateSlug();
        $slug = $this->service->getSlug();

        $this->assertNotNull($slug);
        $this->assertStringContainsString('mairie-de-test', $slug);
        $this->assertStringContainsString('paris', $slug);
    }

    public function testToString(): void
    {
        $this->assertSame('Mairie de Test (Paris)', (string) $this->service);
    }
}
