<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class NominatimService
{
    // Switched to BAN API (Base Adresse Nationale) because Nominatim connection was reset/blocked
    private const BAN_API_URL = 'https://api-adresse.data.gouv.fr';

    public function __construct(
        private HttpClientInterface $client,
        private CacheInterface $cache,
        private \Psr\Log\LoggerInterface $logger
    ) {
    }

    public function reverse(float $lat, float $lon): ?array
    {
        // Round to 5 decimals for cache
        $latClean = round($lat, 5);
        $lonClean = round($lon, 5);
        $cacheKey = sprintf('geo_rev_ban_%s_%s', $latClean, $lonClean);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($latClean, $lonClean) {
            $item->expiresAfter(86400);

            try {
                $response = $this->client->request('GET', self::BAN_API_URL . '/reverse', [
                    'query' => [
                        'lat' => $latClean,
                        'lon' => $lonClean,
                        'limit' => 1
                    ],
                    'timeout' => 5,
                ]);

                if ($response->getStatusCode() !== 200) {
                    return null;
                }

                $data = $response->toArray();
                if (empty($data['features'])) {
                    return null;
                }

                $feature = $data['features'][0];

                // Map BAN format to our expected format
                return [
                    'display_name' => $feature['properties']['label'],
                    'address' => [
                        'street' => $feature['properties']['street'] ?? null,
                        'city' => $feature['properties']['city'] ?? null,
                        'postcode' => $feature['properties']['postcode'] ?? null,
                    ],
                    'lat' => $feature['geometry']['coordinates'][1], // GeoJSON is [lon, lat]
                    'lon' => $feature['geometry']['coordinates'][0],
                ];

            } catch (\Exception $e) {
                $this->logger->error('BAN API error: ' . $e->getMessage());
                return null;
            }
        });
    }

    public function search(string $query): array
    {
        $normalizedQuery = strtolower(trim($query));
        $cacheKey = 'geo_search_ban_' . md5($normalizedQuery);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($query) {
            $item->expiresAfter(86400);

            try {
                $response = $this->client->request('GET', self::BAN_API_URL . '/search', [
                    'query' => [
                        'q' => $query,
                        'limit' => 5
                    ],
                    'timeout' => 5,
                ]);

                if ($response->getStatusCode() !== 200) {
                    return [];
                }

                $data = $response->toArray();
                $results = [];

                foreach ($data['features'] as $feature) {
                    $results[] = [
                        'display_name' => $feature['properties']['label'],
                        'lat' => $feature['geometry']['coordinates'][1],
                        'lon' => $feature['geometry']['coordinates'][0],
                        'address' => [
                            'city' => $feature['properties']['city'] ?? null,
                            'postcode' => $feature['properties']['postcode'] ?? null,
                        ],
                    ];
                }

                return $results;

            } catch (\Exception $e) {
                $this->logger->error('BAN API search error: ' . $e->getMessage());
                return [];
            }
        });
    }
}


