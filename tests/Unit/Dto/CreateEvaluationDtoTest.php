<?php

namespace App\Tests\Unit\Dto;

use App\Dto\CreateEvaluationDto;
use PHPUnit\Framework\TestCase;

class CreateEvaluationDtoTest extends TestCase
{
    public function testEstVerifieAlwaysFalse(): void
    {
        // The constructor never reads 'est_verifie' from input; the property
        // defaults to false and is not overridable via the constructor.
        $dto = new CreateEvaluationDto([
            'note' => 5,
            'service_id' => '550e8400-e29b-41d4-a716-446655440000',
        ]);

        $this->assertFalse($dto->est_verifie, 'est_verifie should always be false regardless of input');
    }

    public function testNoteCastToInt(): void
    {
        $dto = new CreateEvaluationDto([
            'note' => '3',
            'service_id' => '550e8400-e29b-41d4-a716-446655440000',
        ]);

        $this->assertSame(3, $dto->note);
        $this->assertIsInt($dto->note);
    }

    public function testDefaultValues(): void
    {
        $dto = new CreateEvaluationDto([
            'note' => 4,
            'service_id' => '550e8400-e29b-41d4-a716-446655440000',
        ]);

        $this->assertFalse($dto->est_anonyme);
        $this->assertFalse($dto->est_verifie);
        $this->assertNull($dto->commentaire);
        $this->assertNull($dto->user_id);
        $this->assertNull($dto->pseudo_anonyme);
    }

    public function testToArrayContainsAllFields(): void
    {
        $dto = new CreateEvaluationDto([
            'note' => 4,
            'service_id' => '550e8400-e29b-41d4-a716-446655440000',
            'commentaire' => 'Très bien',
            'est_anonyme' => true,
        ]);

        $array = $dto->toArray();
        $this->assertArrayHasKey('note', $array);
        $this->assertArrayHasKey('service_id', $array);
        $this->assertArrayHasKey('est_verifie', $array);
        $this->assertFalse($array['est_verifie']);
        $this->assertSame(4, $array['note']);
        $this->assertSame('Très bien', $array['commentaire']);
    }
}
