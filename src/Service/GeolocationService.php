<?php

namespace App\Service;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class GeolocationService
{
    private const ADRESSE_GOUV_URL = 'https://api-adresse.data.gouv.fr/search';
    private const CACHE_TTL = 86400; // 24h en secondes
    private const BATCH_SIZE = 10;

    public function __construct(
        private HttpClientInterface $httpClient,
        private CacheItemPoolInterface $cache,
        private LoggerInterface $logger
    ) {}

    // ----- Cache helpers -----
    private function cacheGet(string $key): mixed
    {
        try {
            $item = $this->cache->getItem($key);
            return $item->isHit() ? $item->get() : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function cacheSet(string $key, mixed $value, int $ttl): void
    {
        try {
            $item = $this->cache->getItem($key);
            $item->set($value);
            $item->expiresAfter($ttl);
            $this->cache->save($item);
        } catch (\Throwable $e) {
            $this->logger->warning('Cache set failed', ['key' => $key, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Géolocalise une adresse simple (compat ancienne interface)
     */
    public function geocodeAddress(string $address): ?array
    {
        $parsedAddress = $this->parseAddressString($address);
        $results = $this->geocodeBatch(['temp' => $parsedAddress]);
        return $results['temp'] ?? null;
    }

    private function parseAddressString(string $address): array
    {
        $address = trim($address);

        if (preg_match('/^(.+),\s*(\d{5})\s+(.+)$/', $address, $m)) {
            return ['adresse' => trim($m[1]), 'code_postal' => trim($m[2]), 'ville' => trim($m[3])];
        }
        if (preg_match('/^(.+)\s+(\d{5})\s+(.+)$/', $address, $m)) {
            return ['adresse' => trim($m[1]), 'code_postal' => trim($m[2]), 'ville' => trim($m[3])];
        }
        if (preg_match('/^(\d{5})\s+(.+)$/', $address, $m)) {
            return ['adresse' => '', 'code_postal' => trim($m[1]), 'ville' => trim($m[2])];
        }
        return ['adresse' => '', 'code_postal' => '', 'ville' => $address];
    }

    /**
     * @param array<string,array{ville?:string,adresse?:string,code_postal?:string}> $addresses
     * @return array<string,?array{lat:float,lng:float,address?:?string,score?:?float}>
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
        $toFetch   = [];

        // 1) lecture cache
        foreach ($batch as $key => $address) {
            $cacheKey = $this->generateCacheKey($address);
            $cached   = $this->cacheGet($cacheKey);
            if ($cached !== null) {
                $cacheHits[$key] = $cached;
            } else {
                $toFetch[$key] = $address;
            }
        }

        if (empty($toFetch)) {
            return $cacheHits;
        }

        // 2) requêtes HTTP préparées
        /** @var ResponseInterface[] $pending */
        $pending = [];
        foreach ($toFetch as $key => $address) {
            $query = $this->buildSearchQuery($address);
            $pending[$key] = $this->httpClient->request('GET', self::ADRESSE_GOUV_URL, [
                'query'   => ['q' => $query, 'limit' => 1, 'autocomplete' => 1],
                'timeout' => 10,
            ]);
        }

        // 3) stream loop
        $results = [];
        while (!empty($pending)) {
            foreach ($this->httpClient->stream($pending, 30) as $response => $chunk) {
                if (!$chunk->isLast()) {
                    continue;
                }

                $key = array_search($response, $pending, true);

                try {
                    $data   = $response->toArray(false);
                    $result = $this->processGeocodeResponse($data);
                    $results[$key] = $result;

                    $cacheKey = $this->generateCacheKey($toFetch[$key]);
                    if ($result !== null) {
                        $this->cacheSet($cacheKey, $result, self::CACHE_TTL);
                    }
                } catch (\Throwable $e) {
                    $this->logger->error('Erreur géocodage batch', [
                        'key' => $key,
                        'error' => $e->getMessage(),
                    ]);
                    $results[$key] = null;
                }

                unset($pending[$key]);
            }
            usleep(50_000);
        }

        return $cacheHits + $results;
    }

    private function buildSearchQuery(array $address): string
    {
        $ville = trim($address['ville'] ?? '');
        $adresse = trim($address['adresse'] ?? '');
        $codePostal = trim($address['code_postal'] ?? '');

        if (!empty($ville) && empty($adresse) && empty($codePostal)) {
            return $ville;
        }
        return implode(' ', array_filter([$adresse, $codePostal, $ville]));
    }

    private function processGeocodeResponse(array $data): ?array
    {
        if (empty($data['features']) || !is_array($data['features'])) {
            return null;
        }
        $feature = $data['features'][0];
        if (empty($feature['geometry']['coordinates']) || !is_array($feature['geometry']['coordinates'])) {
            return null;
        }
        $coordinates = $feature['geometry']['coordinates'];
        if (count($coordinates) >= 2) {
            return [
                'lat' => (float) $coordinates[1],
                'lng' => (float) $coordinates[0],
                'address' => $feature['properties']['label'] ?? null,
                'score' => $feature['properties']['score'] ?? null
            ];
        }
        return null;
    }

    private function generateCacheKey(array $address): string
    {
        $parts = [
            mb_strtolower(trim($address['ville'] ?? '')),
            mb_strtolower(trim($address['adresse'] ?? '')),
            trim($address['code_postal'] ?? '')
        ];
        // normalise espaces
        $parts = array_map(fn($s) => preg_replace('/\s+/', ' ', $s), $parts);
        return 'geocode_adresse_' . md5(implode('|', $parts));
    }

    public function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371;
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);
        $a = sin($latDelta / 2) ** 2 +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lngDelta / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }

    public function reverseGeocode(float $lat, float $lng): ?array
    {
        $cacheKey = 'reverse_geocode_' . md5($lat . '_' . $lng);

        try {
            $item = $this->cache->getItem($cacheKey);
            if ($item->isHit()) {
                return $item->get();
            }

            $response = $this->httpClient->request('GET', 'https://api-adresse.data.gouv.fr/reverse/', [
                'query' => ['lat' => $lat, 'lon' => $lng],
                'timeout' => 10
            ]);
            $data = $response->toArray(false);

            if (empty($data['features'])) {
                return null;
            }

            $feature = $data['features'][0];
            $properties = $feature['properties'] ?? [];

            $result = [
                'label' => $properties['label'] ?? null,
                'name' => $properties['name'] ?? null,
                'ville' => $properties['city'] ?? null,
                'code_postal' => $properties['postcode'] ?? null,
                'contexte' => $properties['context'] ?? null,
                'type' => $properties['type'] ?? null,
                'score' => $properties['score'] ?? 0,
                'source' => 'api-adresse-gouv-reverse'
            ];

            $item->set($result);
            $item->expiresAfter(self::CACHE_TTL);
            $this->cache->save($item);

            return $result;
        } catch (\Throwable $e) {
            $this->logger->error('Erreur reverse geocoding API Adresse', [
                'lat' => $lat,
                'lng' => $lng,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function validateCoordinates(?float $lat, ?float $lng): bool
    {
        if ($lat === null || $lng === null) return false;
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) return false;
        if ($lat === 0.0 && $lng === 0.0) return false;

        if ($lat < 41 || $lat > 51 || $lng < -5 || $lng > 10) {
            $this->logger->info('Coordonnées hors France métropolitaine', ['lat' => $lat, 'lng' => $lng]);
        }
        return true;
    }

    public function filterByScore(array $results, float $minScore = 0.5): array
    {
        return array_filter($results, fn($r) => $r && isset($r['score']) && $r['score'] >= $minScore);
    }

    public function groupByCity(array $results): array
    {
        $grouped = [];
        foreach ($results as $key => $result) {
            if ($result && !empty($result['ville'])) {
                $ville = $result['ville'];
                $grouped[$ville] ??= [];
                $grouped[$ville][$key] = $result;
            }
        }
        return $grouped;
    }

    public function geocodeAddressWithFallback(string $address): ?array
    {
        try {
            $result = $this->geocodeAddress($address);
            if ($result) return $result;
        } catch (\Throwable $e) {
            $this->logger->warning('Premier essai échoué, tentative de fallback', ['address' => $address, 'error' => $e->getMessage()]);
        }

        $cleanAddress = $this->cleanAddress($address);
        if ($cleanAddress !== $address) {
            try {
                $result = $this->geocodeAddress($cleanAddress);
                if ($result) return $result;
            } catch (\Throwable) {}
        }

        if (preg_match('/(\d{5})\s+([^,]+)$/', $address, $m)) {
            $fallbackAddress = $m[1] . ' ' . $m[2];
            try {
                return $this->geocodeAddress($fallbackAddress);
            } catch (\Throwable) {}
        }
        return null;
    }

    private function cleanAddress(string $address): string
    {
        $replacements = [
            'é' => 'e','è' => 'e','ê' => 'e',
            'à' => 'a','ç' => 'c','ô' => 'o','û' => 'u','ù' => 'u','î' => 'i','â' => 'a'
        ];
        $cleaned = str_replace(array_keys($replacements), array_values($replacements), $address);
        $cleaned = preg_replace('/\s+/', ' ', $cleaned);
        return trim($cleaned);
    }
}
