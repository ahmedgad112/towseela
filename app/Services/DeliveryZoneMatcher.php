<?php

namespace App\Services;

use App\Models\DeliveryZone;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DeliveryZoneMatcher
{
    /**
     * @param  array<string, mixed>  $address
     */
    public function match(array $address, string $displayName): ?DeliveryZone
    {
        $governorate = $this->resolveGovernorate($address, $displayName);
        $placeTokens = $this->placeTokens($address, $displayName, $governorate);

        $best = $this->zones()
            ->map(fn (DeliveryZone $zone): array => [
                'zone' => $zone,
                'score' => $this->score($zone, $placeTokens, $governorate),
            ])
            ->filter(fn (array $candidate): bool => $candidate['score'] > 0)
            ->sortByDesc(fn (array $candidate): int => $candidate['score'])
            ->first();

        return $best['zone'] ?? null;
    }

    /**
     * @param  array<string, mixed>  $address
     */
    public function resolveGovernorate(array $address, string $displayName): ?string
    {
        $candidates = [
            $address['state'] ?? null,
            $address['ISO3166-2-lvl4'] ?? null,
            $address['region'] ?? null,
            $address['city'] ?? null,
            $address['town'] ?? null,
            $displayName,
        ];

        foreach ($candidates as $candidate) {
            $governorate = $this->knownGovernorate((string) $candidate);

            if ($governorate !== null) {
                return $governorate;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $address
     */
    public function resolvePlace(array $address, string $displayName, ?string $governorate): string
    {
        foreach (['suburb', 'city_district', 'neighbourhood', 'quarter', 'city', 'town', 'village'] as $field) {
            $value = trim((string) ($address[$field] ?? ''));

            if ($value === '' || $this->sameName($value, $governorate)) {
                continue;
            }

            return $value;
        }

        return $displayName;
    }

    /**
     * @param  list<string>  $placeTokens
     */
    private function score(DeliveryZone $zone, array $placeTokens, ?string $governorate): int
    {
        $zoneMatchesPlace = $this->namesOverlap($placeTokens, $this->zoneNames($zone));
        $sameGovernorate = $governorate !== null && $this->sameName($governorate, $zone->governorate);
        $zoneIsGovernorate = $governorate !== null && $this->namesOverlap([$governorate], $this->zoneNames($zone));

        if ($zoneMatchesPlace && filled($zone->governorate) && $governorate !== null && ! $sameGovernorate) {
            return 0;
        }

        if ($zoneMatchesPlace && ($sameGovernorate || blank($zone->governorate))) {
            return 100 + Str::length($zone->name_ar);
        }

        if ($zoneIsGovernorate && ($sameGovernorate || blank($zone->governorate))) {
            return 20;
        }

        return 0;
    }

    /**
     * @param  array<string, mixed>  $address
     * @return list<string>
     */
    private function placeTokens(array $address, string $displayName, ?string $governorate): array
    {
        $tokens = [
            $address['suburb'] ?? null,
            $address['city_district'] ?? null,
            $address['neighbourhood'] ?? null,
            $address['quarter'] ?? null,
            $address['city'] ?? null,
            $address['town'] ?? null,
            $address['village'] ?? null,
            ...preg_split('/[,،]+/u', $displayName) ?: [],
        ];

        return collect($tokens)
            ->map(fn (mixed $token): string => trim((string) $token))
            ->filter(fn (string $token): bool => $token !== '' && ! $this->sameName($token, $governorate))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    private function zoneNames(DeliveryZone $zone): array
    {
        return collect([$zone->name_ar, $zone->name_en, ...($zone->aliases ?? [])])
            ->filter(fn (mixed $name): bool => filled($name))
            ->map(fn (mixed $name): string => (string) $name)
            ->all();
    }

    /**
     * @param  list<string>  $left
     * @param  list<string>  $right
     */
    private function namesOverlap(array $left, array $right): bool
    {
        foreach ($left as $leftName) {
            foreach ($right as $rightName) {
                if ($this->sameName($leftName, $rightName) || $this->containsName($leftName, $rightName)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function containsName(string $haystack, string $needle): bool
    {
        $haystack = $this->normalize($haystack);
        $needle = $this->normalize($needle);

        return Str::length($needle) >= 3
            && $haystack !== ''
            && (str_contains($haystack, $needle) || str_contains($needle, $haystack));
    }

    private function sameName(?string $left, ?string $right): bool
    {
        if (blank($left) || blank($right)) {
            return false;
        }

        return $this->normalize((string) $left) === $this->normalize((string) $right);
    }

    private function knownGovernorate(string $value): ?string
    {
        $normalized = $this->normalize($value);

        if ($normalized === '') {
            return null;
        }

        foreach (config('towseela.governorates') as $governorate) {
            $names = [
                $governorate['name'],
                ...($governorate['aliases'] ?? []),
            ];

            foreach ($names as $name) {
                $candidate = $this->normalize((string) $name);

                if ($candidate !== '' && (str_contains($normalized, $candidate) || $normalized === $candidate)) {
                    return $governorate['name'];
                }
            }
        }

        return null;
    }

    private function normalize(string $value): string
    {
        $value = Str::of($value)
            ->lower()
            ->replace(['أ', 'إ', 'آ'], 'ا')
            ->replace('ى', 'ي')
            ->replace('ة', 'ه')
            ->replace(['محافظة', 'governorate', 'muhafazat', 'muhafazah'], '')
            ->replaceMatches('/[^\p{L}\p{N}\s]+/u', ' ')
            ->squish()
            ->toString();

        if (Str::startsWith($value, 'ال') && Str::length($value) > 4) {
            $value = Str::substr($value, 2);
        }

        return $value;
    }

    /**
     * @return Collection<int, DeliveryZone>
     */
    private function zones(): Collection
    {
        return DeliveryZone::query()->active()->orderBy('name_ar')->orderBy('id')->get();
    }
}
