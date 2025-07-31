<?php

namespace App\Dto;

class EvaluationFilterDto
{
    public function __construct(
        public readonly string $search = '',
        public readonly int $page = 1,
        public readonly int $limit = 25,
        public readonly string $sortField = 'createdAt',
        public readonly string $sortOrder = 'desc',
        public readonly ?string $statut = null,
        public readonly ?int $note_min = null,
        public readonly ?int $note_max = null,
        public readonly ?string $service_id = null,
        public readonly ?string $user_id = null,
        public readonly ?bool $est_verifie = null,
        public readonly ?bool $est_anonyme = null
    ) {}

    public function toArray(): array
    {
        return [
            'search' => $this->search,
            'page' => $this->page,
            'limit' => $this->limit,
            'sortField' => $this->sortField,
            'sortOrder' => $this->sortOrder,
            'statut' => $this->statut,
            'note_min' => $this->note_min,
            'note_max' => $this->note_max,
            'service_id' => $this->service_id,
            'user_id' => $this->user_id,
            'est_verifie' => $this->est_verifie,
            'est_anonyme' => $this->est_anonyme
        ];
    }
}
