<?php

namespace App\Dto;

class ServicePublicFilterDto
{
    public function __construct(
        public readonly string $search,
        public readonly int $page,
        public readonly int $limit,
        public readonly string $sortField,
        public readonly string $sortOrder,
        public readonly ?string $statut,
        public readonly ?string $ville,
        public readonly ?string $categorie,
        public readonly ?string $source
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
            'ville' => $this->ville,
            'categorie' => $this->categorie,
            'source' => $this->source,
        ];
    }
}
