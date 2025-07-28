<?php

namespace App\Dto;

class UserFilterDto
{
    public function __construct(
        public string $search = '',
        public int $page = 1,
        public int $limit = 10,
        public string $sortField = 'id',
        public string $sortOrder = 'asc',
        public ?string $role = null,
        public ?string $statut = null
    ) {}

    public function toArray(): array
    {
        return [
            'search' => $this->search,
            'page' => $this->page,
            'limit' => $this->limit,
            'sortField' => $this->sortField,
            'sortOrder' => $this->sortOrder,
            'role' => $this->role,
            'statut' => $this->statut
        ];
    }
}
