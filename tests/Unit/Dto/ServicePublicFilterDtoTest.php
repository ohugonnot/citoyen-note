<?php

namespace App\Tests\Unit\Dto;

use App\Dto\ServicePublicFilterDto;
use PHPUnit\Framework\TestCase;

class ServicePublicFilterDtoTest extends TestCase
{
    private function makeDto(array $overrides = []): ServicePublicFilterDto
    {
        return new ServicePublicFilterDto(
            search: $overrides['search'] ?? '',
            page: $overrides['page'] ?? 1,
            limit: $overrides['limit'] ?? 20,
            sortField: $overrides['sortField'] ?? 'nom',
            sortOrder: $overrides['sortOrder'] ?? 'asc',
            statut: $overrides['statut'] ?? null,
            ville: $overrides['ville'] ?? null,
            categorie: $overrides['categorie'] ?? null,
            source: $overrides['source'] ?? null,
        );
    }

    public function testConstructorSetsAllProperties(): void
    {
        $dto = new ServicePublicFilterDto(
            search: 'mairie',
            page: 2,
            limit: 10,
            sortField: 'ville',
            sortOrder: 'desc',
            statut: 'ACTIF',
            ville: 'Paris',
            categorie: 'sante',
            source: 'datagouv',
        );

        $this->assertSame('mairie', $dto->search);
        $this->assertSame(2, $dto->page);
        $this->assertSame(10, $dto->limit);
        $this->assertSame('ville', $dto->sortField);
        $this->assertSame('desc', $dto->sortOrder);
        $this->assertSame('ACTIF', $dto->statut);
        $this->assertSame('Paris', $dto->ville);
        $this->assertSame('sante', $dto->categorie);
        $this->assertSame('datagouv', $dto->source);
    }

    public function testNullableFieldsAcceptNull(): void
    {
        $dto = $this->makeDto();

        $this->assertNull($dto->statut);
        $this->assertNull($dto->ville);
        $this->assertNull($dto->categorie);
        $this->assertNull($dto->source);
    }

    public function testToArrayReturnsExpectedKeys(): void
    {
        $dto = $this->makeDto(['search' => 'hôpital', 'statut' => 'ACTIF']);

        $array = $dto->toArray();

        $this->assertArrayHasKey('search', $array);
        $this->assertArrayHasKey('page', $array);
        $this->assertArrayHasKey('limit', $array);
        $this->assertArrayHasKey('sortField', $array);
        $this->assertArrayHasKey('sortOrder', $array);
        $this->assertArrayHasKey('statut', $array);
        $this->assertArrayHasKey('ville', $array);
        $this->assertArrayHasKey('categorie', $array);
        $this->assertArrayHasKey('source', $array);
    }

    public function testToArrayReturnsCorrectValues(): void
    {
        $dto = $this->makeDto(['search' => 'bibliothèque', 'page' => 3, 'limit' => 5]);

        $array = $dto->toArray();

        $this->assertSame('bibliothèque', $array['search']);
        $this->assertSame(3, $array['page']);
        $this->assertSame(5, $array['limit']);
    }

    public function testWordsReturnsEmptyArrayForEmptySearch(): void
    {
        $dto = $this->makeDto(['search' => '']);

        $this->assertSame([], $dto->words());
    }

    public function testWordsReturnsEmptyArrayForBlankSearch(): void
    {
        $dto = $this->makeDto(['search' => '   ']);

        $this->assertSame([], $dto->words());
    }

    public function testWordsSplitsSingleWord(): void
    {
        $dto = $this->makeDto(['search' => 'mairie']);

        $this->assertSame(['mairie'], $dto->words());
    }

    public function testWordsSplitsMultipleWords(): void
    {
        $dto = $this->makeDto(['search' => 'mairie paris']);

        $this->assertSame(['mairie', 'paris'], $dto->words());
    }

    public function testWordsSplitsOnMultipleSpaces(): void
    {
        $dto = $this->makeDto(['search' => 'mairie   de   paris']);

        $this->assertSame(['mairie', 'de', 'paris'], $dto->words());
    }

    public function testWordsTrimsSurroundingWhitespace(): void
    {
        $dto = $this->makeDto(['search' => '  mairie paris  ']);

        $this->assertSame(['mairie', 'paris'], $dto->words());
    }
}
