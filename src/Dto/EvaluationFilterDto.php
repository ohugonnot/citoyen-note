<?php

namespace App\Dto;

class EvaluationFilterDto
{
    public readonly array $searchTerms;

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
        public readonly ?bool $est_anonyme = null,
    ) {
        // Traiter la recherche en mots séparés
        $this->searchTerms = $this->parseSearchTerms($search);
    }

    /**
     * Parse la chaîne de recherche en mots individuels
     * Supprime les mots vides et nettoie les termes
     */
    private function parseSearchTerms(string $search): array
    {
        if (empty(trim($search))) {
            return [];
        }

        // Diviser par espaces et filtrer les mots vides
        $terms = array_filter(
            array_map('trim', explode(' ', trim($search))),
            fn($term) => !empty($term) && strlen($term) >= 2
        );

        // Nettoyer et normaliser les termes
        return array_map(function($term) {
            // Supprimer les caractères spéciaux mais garder les accents
            $term = preg_replace('/[^\p{L}\p{N}\-\']/u', '', $term);
            return strtolower($term);
        }, array_values($terms));
    }

    /**
     * Vérifie si une recherche est active
     */
    public function hasSearch(): bool
    {
        return !empty($this->searchTerms);
    }

    /**
     * Retourne le nombre de termes de recherche
     */
    public function getSearchTermsCount(): int
    {
        return count($this->searchTerms);
    }

    /**
     * Retourne une version SQL-safe des termes de recherche
     */
    public function getSqlSearchTerms(): array
    {
        return array_map(fn($term) => '%' . $term . '%', $this->searchTerms);
    }

    public function toArray(): array
    {
        return [
            'search' => $this->search,
            'searchTerms' => $this->searchTerms,
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
            'est_anonyme' => $this->est_anonyme,
        ];
    }
}