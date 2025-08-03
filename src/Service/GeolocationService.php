<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class GeolocationService
{
    private const ADRESSE_GOUV_URL = 'https://api-adresse.data.gouv.fr/search';
    private const CACHE_TTL = 86400; // 24h en secondes
    private const BATCH_SIZE = 10;

    public function __construct(
        private HttpClientInterface $httpClient,
        private CacheInterface $cache,
        private LoggerInterface $logger
    ) {}

    /**
     * Géolocalise une adresse simple (pour compatibilité avec l'ancienne interface)
     */
    public function geocodeAddress(string $address): ?array
    {
        // Parser l'adresse string en composants
        $parsedAddress = $this->parseAddressString($address);

        // Utiliser votre méthode geocodeBatch existante
        $results = $this->geocodeBatch(['temp' => $parsedAddress]);
        return $results['temp'] ?? null;
    }

    /**
     * Parse une string d'adresse en composants pour votre service
     */
    private function parseAddressString(string $address): array
    {
        $address = trim($address);

        // Pattern pour extraire : [adresse], [code_postal] [ville]
        if (preg_match('/^(.+),\s*(\d{5})\s+(.+)$/', $address, $matches)) {
            return [
                'adresse' => trim($matches[1]),
                'code_postal' => trim($matches[2]),
                'ville' => trim($matches[3])
            ];
        }

        // Pattern alternatif : [adresse] [code_postal] [ville] (sans virgule)
        if (preg_match('/^(.+)\s+(\d{5})\s+(.+)$/', $address, $matches)) {
            return [
                'adresse' => trim($matches[1]),
                'code_postal' => trim($matches[2]),
                'ville' => trim($matches[3])
            ];
        }

        // Pattern pour juste ville + code postal
        if (preg_match('/^(\d{5})\s+(.+)$/', $address, $matches)) {
            return [
                'adresse' => '',
                'code_postal' => trim($matches[1]),
                'ville' => trim($matches[2])
            ];
        }

        // Si aucun pattern, tout dans ville
        return [
            'adresse' => '',
            'code_postal' => '',
            'ville' => $address
        ];
    }

    /**
     * Géolocalise plusieurs adresses en lot
     *
     * @param array $addresses Tableau d'adresses avec les clés : ville, adresse, code_postal
     * @return array Résultats indexés par la clé d'origine
     */
    public function geocodeBatch(array $addresses): array
    {
        if (empty($addresses)) {
            return [];
        }

        $results = [];
        $batches = array_chunk($addresses, self::BATCH_SIZE, true);

        foreach ($batches as $batch) {
            $batchResults = $this->processBatch($batch);
            $results = array_merge($results, $batchResults);
        }

        return $results;
    }

    private function processBatch(array $batch): array
    {
        $cacheHits = [];
        $toFetch    = [];

        // 1) Vérifier le cache
        foreach ($batch as $key => $address) {
            $cacheKey = $this->generateCacheKey($address);
            try {
                $cached = $this->cache->get($cacheKey, fn(ItemInterface $item) => null);
                if (null !== $cached) {
                    $cacheHits[$key] = $cached;
                    continue;
                }
            } catch (\Throwable $e) {
                // on retombe sur un fetch si le cache plante
            }
            $toFetch[$key] = $address;
        }

        if (empty($toFetch)) {
            return $cacheHits;
        }

        // 2) Préparer toutes les requêtes (elles restent “pending”)
        /** @var ResponseInterface[] $pending */
        $pending = [];
        foreach ($toFetch as $key => $address) {
            $query = $this->buildSearchQuery($address);
            $pending[$key] = $this->httpClient->request('GET', self::ADRESSE_GOUV_URL, [
                'query'   => ['q' => $query, 'limit' => 1],
                'timeout' => 10,
            ]);
        }

        // 3) Traiter les réponses dès qu'elles arrivent, en respectant max_host_connections
        $results = [];
        while (!empty($pending)) {
            foreach ($this->httpClient->stream($pending, 30) as $response => $chunk) {
                if (! $chunk->isLast()) {
                    continue;
                }

                $key = array_search($response, $pending, true);

                try {
                    $data = $response->toArray(false);
                    $result = $this->processGeocodeResponse($data);
                    $results[$key] = $result;

                    // mise en cache etc.

                } catch (\Throwable $e) {
                    $this->logger->error('Erreur géocodage batch', [
                        'key' => $key,
                        'error' => $e->getMessage(),
                    ]);
                    $results[$key] = null;
                }

                unset($pending[$key]); // très important !
            }

            // petit sleep si nécessaire pour éviter boucle infinie
            usleep(50000); // 50ms
        }
        if (!empty($pending)) {
            dump($pending);
        }
        return $cacheHits + $results;
    }

    /**
     * Construit la requête de recherche
     */
    private function buildSearchQuery(array $address): string
    {
        $ville = trim($address['ville'] ?? '');
        $adresse = trim($address['adresse'] ?? '');
        $codePostal = trim($address['code_postal'] ?? '');

        // Si ville contient déjà tout (votre cas actuel)
        if (!empty($ville) && empty($adresse) && empty($codePostal)) {
            return $ville;
        }

        // Construction classique
        $parts = array_filter([$adresse, $codePostal, $ville]);
        return implode(' ', $parts);
    }

    private function processGeocodeResponse(array $data): ?array
    {
        // Vérifier si on a des résultats
        if (empty($data['features']) || !is_array($data['features'])) {
            return null;
        }

        $feature = $data['features'][0];

        // Vérifier la structure de la géométrie
        if (empty($feature['geometry']) ||
            empty($feature['geometry']['coordinates']) ||
            !is_array($feature['geometry']['coordinates'])) {
            return null;
        }

        $coordinates = $feature['geometry']['coordinates'];

        // L'API française retourne [longitude, latitude] (format GeoJSON standard)
        if (count($coordinates) >= 2) {
            return [
                'lat' => (float) $coordinates[1], // latitude = index 1
                'lng' => (float) $coordinates[0], // longitude = index 0
                'address' => $feature['properties']['label'] ?? null,
                'score' => $feature['properties']['score'] ?? null
            ];
        }

        return null;
    }

    /**
     * Génère une clé de cache pour une adresse
     */
    private function generateCacheKey(array $address): string
    {
        $key = implode('|', [
            trim($address['ville'] ?? ''),
            trim($address['adresse'] ?? ''),
            $address['code_postal'] ?? ''
        ]);

        return 'geocode_adresse_' . md5($key);
    }

    /**
     * Calcule la distance entre deux points (formule haversine)
     */
    public function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // Rayon de la Terre en km

        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lngDelta / 2) * sin($lngDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Recherche inverse : trouve l'adresse à partir des coordonnées
     */
    public function reverseGeocode(float $lat, float $lng): ?array
    {
        $cacheKey = 'reverse_geocode_' . md5($lat . '_' . $lng);

        try {
            return $this->cache->get($cacheKey, function (ItemInterface $item) use ($lat, $lng) {
                $item->expiresAfter(self::CACHE_TTL);

                $response = $this->httpClient->request('GET', 'https://api-adresse.data.gouv.fr/reverse/', [
                    'query' => [
                        'lat' => $lat,
                        'lon' => $lng,
                    ],
                    'timeout' => 10
                ]);

                $data = $response->toArray();

                if (empty($data['features'])) {
                    return null;
                }

                $feature = $data['features'][0];
                $properties = $feature['properties'] ?? [];

                return [
                    'label' => $properties['label'] ?? null,
                    'name' => $properties['name'] ?? null,
                    'ville' => $properties['city'] ?? null,
                    'code_postal' => $properties['postcode'] ?? null,
                    'contexte' => $properties['context'] ?? null,
                    'type' => $properties['type'] ?? null,
                    'score' => $properties['score'] ?? 0,
                    'source' => 'api-adresse-gouv-reverse'
                ];
            });

        } catch (\Exception $e) {
            $this->logger->error('Erreur reverse geocoding API Adresse', [
                'lat' => $lat,
                'lng' => $lng,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Valide des coordonnées GPS
     */
    public function validateCoordinates(?float $lat, ?float $lng): bool
    {
        if ($lat === null || $lng === null) {
            return false;
        }

        // Vérifier que les coordonnées sont dans des plages valides
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return false;
        }

        // Vérifier que ce ne sont pas des coordonnées "nulles"
        if ($lat === 0.0 && $lng === 0.0) {
            return false;
        }

        // Pour la France métropolitaine, vérification approximative
        if ($lat < 41 || $lat > 51 || $lng < -5 || $lng > 10) {
            // Log pour debug mais ne pas rejeter (DOM-TOM)
            $this->logger->info('Coordonnées hors France métropolitaine', [
                'lat' => $lat,
                'lng' => $lng
            ]);
        }

        return true;
    }

    /**
     * Filtre les résultats par score de qualité
     */
    public function filterByScore(array $results, float $minScore = 0.5): array
    {
        return array_filter($results, function ($result) use ($minScore) {
            return $result && isset($result['score']) && $result['score'] >= $minScore;
        });
    }

    /**
     * Groupe les résultats par ville
     */
    public function groupByCity(array $results): array
    {
        $grouped = [];

        foreach ($results as $key => $result) {
            if ($result && !empty($result['ville'])) {
                $ville = $result['ville'];
                if (!isset($grouped[$ville])) {
                    $grouped[$ville] = [];
                }
                $grouped[$ville][$key] = $result;
            }
        }

        return $grouped;
    }

    /**
     * Géolocalise une adresse avec plusieurs tentatives de fallback
     */
    public function geocodeAddressWithFallback(string $address): ?array
    {
        try {
            $result = $this->geocodeAddress($address);
            if ($result) {
                return $result;
            }
        } catch (\Exception $e) {
            $this->logger->warning('Premier essai échoué, tentative de fallback', [
                'address' => $address,
                'error' => $e->getMessage()
            ]);
        }

        // Tentative 2: Nettoyer l'adresse
        $cleanAddress = $this->cleanAddress($address);
        if ($cleanAddress !== $address) {
            try {
                $result = $this->geocodeAddress($cleanAddress);
                if ($result) {
                    return $result;
                }
            } catch (\Exception $e) {
                // Continuer vers la tentative 3
            }
        }

        // Tentative 3: Extraire seulement ville + code postal
        if (preg_match('/(\d{5})\s+([^,]+)$/', $address, $matches)) {
            $fallbackAddress = $matches[1] . ' ' . $matches[2];
            try {
                return $this->geocodeAddress($fallbackAddress);
            } catch (\Exception $e) {
                // Dernière tentative échouée
            }
        }

        return null;
    }

    /**
     * Nettoie une adresse en enlevant les caractères problématiques
     */
    private function cleanAddress(string $address): string
    {
        // Nettoyer les caractères qui peuvent poser problème
        $cleaned = $address;

        // Remplacer les caractères accentués problématiques
        $replacements = [
            'é' => 'e',
            'è' => 'e',
            'ê' => 'e',
            'à' => 'a',
            'ç' => 'c',
            'ô' => 'o',
            'û' => 'u',
            'ù' => 'u',
            'î' => 'i',
            'â' => 'a'
        ];

        $cleaned = str_replace(array_keys($replacements), array_values($replacements), $cleaned);

        // Nettoyer les espaces multiples
        $cleaned = preg_replace('/\s+/', ' ', $cleaned);

        return trim($cleaned);
    }
}
