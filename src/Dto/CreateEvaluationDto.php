<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class CreateEvaluationDto
{
    #[Assert\NotNull]
    #[Assert\Range(min: 1, max: 5)]
    public int $note;

    #[Assert\Length(max: 2000)]
    public ?string $commentaire = null;

    public ?array $criteres_specifiques = null;

    public bool $est_anonyme = false;

    public bool $est_verifie = false;

    #[Assert\NotBlank]
    #[Assert\Uuid]
    public string $service_id;

    // Accepter int, string ou null sans validation UUID
    public string|int|null $user_id = null;

    public function __construct(array $data = [])
    {
        $this->note = (int)($data['note'] ?? 1);
        $this->commentaire = $data['commentaire'] ?? null;
        $this->criteres_specifiques = $data['criteres_specifiques'] ?? null;
        $this->est_anonyme = (bool)($data['est_anonyme'] ?? false);
        $this->est_verifie = (bool)($data['est_verifie'] ?? false);
        $this->service_id = $data['service_id'] ?? '';
        $this->user_id = $data['user_id'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'note' => $this->note,
            'commentaire' => $this->commentaire,
            'criteres_specifiques' => $this->criteres_specifiques,
            'est_anonyme' => $this->est_anonyme,
            'est_verifie' => $this->est_verifie,
            'service_id' => $this->service_id,
            'user_id' => $this->user_id,
        ];
    }
}
