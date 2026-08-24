<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Geocodificación contra Nominatim (OpenStreetMap): sin API key ni costo.
 *
 * Nominatim exige un User-Agent identificable y permite ~1 petición por
 * segundo, así que se llama SIEMPRE desde el servidor (nunca desde el
 * navegador del asesor) y se cachea cada consulta.
 */
class GeocodingService
{
    private const SEARCH_URL = 'https://nominatim.openstreetmap.org/search';

    private const REVERSE_URL = 'https://nominatim.openstreetmap.org/reverse';

    private const CACHE_TTL_DAYS = 30;

    /**
     * Dirección → lista de candidatos, del más probable al menos.
     *
     * @return array<int, array{latitude: float, longitude: float, display_name: string, postal_code: ?string}>
     */
    public function search(string $address, int $limit = 1): array
    {
        $address = trim(preg_replace('/\s+/', ' ', $address));

        if ($address === '') {
            return [];
        }

        return Cache::remember(
            sprintf('geocode:search:%d:%s', $limit, md5(mb_strtolower($address))),
            now()->addDays(self::CACHE_TTL_DAYS),
            fn () => $this->request(self::SEARCH_URL, [
                'q' => $address,
                'countrycodes' => 'mx',
                'limit' => $limit,
                'addressdetails' => 1,
            ])
        );
    }

    /**
     * Coordenadas → dirección (para sugerir CP y colonia al mover el pin).
     *
     * @return array{latitude: float, longitude: float, display_name: string, postal_code: ?string}|null
     */
    public function reverse(float $latitude, float $longitude): ?array
    {
        return Cache::remember(
            sprintf('geocode:reverse:%.5f,%.5f', $latitude, $longitude),
            now()->addDays(self::CACHE_TTL_DAYS),
            fn () => $this->request(self::REVERSE_URL, [
                'lat' => $latitude,
                'lon' => $longitude,
                'addressdetails' => 1,
            ])[0] ?? null
        );
    }

    /**
     * Nominatim devuelve una lista en /search y un objeto suelto en /reverse;
     * aquí ambos se normalizan a lista para tener un solo camino de salida.
     *
     * @param  array<string, mixed>  $query
     * @return array<int, array{latitude: float, longitude: float, display_name: string, postal_code: ?string}>
     */
    private function request(string $url, array $query): array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => config('services.nominatim.user_agent'),
                'Accept-Language' => 'es',
            ])
                ->timeout(config('services.nominatim.timeout'))
                ->get($url, $query + ['format' => 'jsonv2']);
        } catch (ConnectionException $e) {
            Log::warning('geocode_connection_failed', ['message' => $e->getMessage()]);

            return [];
        }

        if ($response->failed()) {
            Log::warning('geocode_failed', ['status' => $response->status()]);

            return [];
        }

        $payload = $response->json();
        $places = array_is_list($payload ?? []) ? $payload : [$payload];

        return collect($places)
            ->filter(fn ($place) => is_array($place) && isset($place['lat'], $place['lon']))
            ->map(fn ($place) => [
                'latitude' => round((float) $place['lat'], 7),
                'longitude' => round((float) $place['lon'], 7),
                'display_name' => $place['display_name'] ?? '',
                'postal_code' => $place['address']['postcode'] ?? null,
            ])
            ->values()
            ->all();
    }
}
