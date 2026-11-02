<?php

namespace App\Services;

use App\Support\PlatformSettings;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GeocodingService
{
    public function __construct(
        private DeliveryZoneMatcher $zones,
        private PlatformSettings $platformSettings,
    ) {}

    /**
     * @return array{line1: string, city: string, place: string, governorate: string|null, display_name: string, lat: float, lng: float, delivery_zone_id: int|null}|null
     */
    public function reverse(float $lat, float $lng): ?array
    {
        $key = sprintf('geocode:raw:reverse:v2:%.4f:%.4f', $lat, $lng);
        $cached = Cache::get($key);

        if (! is_array($cached)) {
            $cached = $this->request('reverse', [
                'lat' => $lat,
                'lon' => $lng,
                'format' => 'json',
                'addressdetails' => 1,
            ]);

            if (! is_array($cached) || $cached === []) {
                return null;
            }

            Cache::put($key, $cached, now()->addHours(12));
        }

        return $this->normalizePlace($cached);
    }

    /**
     * @return list<array{line1: string, city: string, place: string, governorate: string|null, display_name: string, lat: float, lng: float, delivery_zone_id: int|null}>
     */
    public function search(string $query): array
    {
        $results = $this->request('search', [
            'q' => $query,
            'format' => 'json',
            'addressdetails' => 1,
            'limit' => 5,
            'countrycodes' => 'eg',
        ]);

        if (! is_array($results)) {
            return [];
        }

        return collect($results)
            ->filter(fn (mixed $place): bool => is_array($place))
            ->map(fn (array $place): ?array => $this->normalizePlace($place))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>|list<array<string, mixed>>|null
     */
    private function request(string $path, array $query): ?array
    {
        try {
            $response = Http::connectTimeout(3)
                ->timeout(8)
                ->withUserAgent((string) config('towseela.geocoding.user_agent'))
                ->withHeaders(['Accept-Language' => 'ar'])
                ->acceptJson()
                ->get(rtrim((string) config('towseela.geocoding.base_url'), '/').'/'.$path, $query);
        } catch (ConnectionException) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $payload = $response->json();

        return is_array($payload) ? $payload : null;
    }

    /**
     * @param  array<string, mixed>  $place
     * @return array{line1: string, city: string, place: string, governorate: string|null, display_name: string, lat: float, lng: float, delivery_zone_id: int|null}|null
     */
    private function normalizePlace(array $place): ?array
    {
        $displayName = trim((string) ($place['display_name'] ?? ''));
        $lat = isset($place['lat']) ? (float) $place['lat'] : null;
        $lng = isset($place['lon']) ? (float) $place['lon'] : null;

        if ($displayName === '' || $lat === null || $lng === null) {
            return null;
        }

        $address = is_array($place['address'] ?? null) ? $place['address'] : [];
        $governorate = $this->zones->resolveGovernorate($address, $displayName);
        $placeName = $this->zones->resolvePlace($address, $displayName, $governorate);
        $city = $governorate ?: $this->resolveCity($address);

        return [
            'line1' => $this->buildLine1($address, $displayName, $placeName),
            'city' => $city,
            'place' => $placeName,
            'governorate' => $governorate,
            'display_name' => Str::limit($displayName, 255, ''),
            'lat' => $lat,
            'lng' => $lng,
            'delivery_zone_id' => $this->zones->match($address, $displayName)?->id,
        ];
    }

    /**
     * @param  array<string, mixed>  $address
     */
    private function buildLine1(array $address, string $displayName, string $placeName): string
    {
        $street = $address['road'] ?? $address['pedestrian'] ?? $address['neighbourhood'] ?? null;
        $parts = array_filter([$street, $placeName !== $street ? $placeName : null], fn (mixed $part): bool => filled($part));

        return Str::limit($parts === [] ? $displayName : implode('، ', $parts), 255, '');
    }

    /**
     * @param  array<string, mixed>  $address
     */
    private function resolveCity(array $address): string
    {
        return (string) ($address['city'] ?? $address['town'] ?? $address['village'] ?? $address['state'] ?? $this->platformSettings->map()['default_city']);
    }
}
