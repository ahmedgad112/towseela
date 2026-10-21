<?php

namespace Tests\Feature;

use App\Models\DeliveryZone;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LocationLookupTest extends TestCase
{
    public function test_reverse_lookup_returns_address_for_coordinates(): void
    {
        Http::preventStrayRequests();
        $zone = DeliveryZone::factory()->create([
            'name_ar' => 'المعادي',
            'name_en' => 'Maadi',
            'governorate' => 'القاهرة',
            'aliases' => ['المعادى'],
            'is_active' => true,
        ]);

        Http::fake([
            'nominatim.openstreetmap.org/reverse*' => Http::response([
                'display_name' => 'شارع 9، المعادي، القاهرة، مصر',
                'lat' => '29.9602000',
                'lon' => '31.2569000',
                'address' => [
                    'road' => 'شارع 9',
                    'suburb' => 'المعادي',
                    'city' => 'القاهرة',
                    'state' => 'القاهرة',
                ],
            ]),
        ]);

        $this->getJson('/location/reverse?lat=29.9602&lng=31.2569')
            ->assertOk()
            ->assertJsonPath('line1', 'شارع 9، المعادي')
            ->assertJsonPath('city', 'القاهرة')
            ->assertJsonPath('governorate', 'القاهرة')
            ->assertJsonPath('place', 'المعادي')
            ->assertJsonPath('delivery_zone_id', $zone->id);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/reverse'));
    }

    public function test_search_lookup_returns_matching_places(): void
    {
        Http::preventStrayRequests();

        Http::fake([
            'nominatim.openstreetmap.org/search*' => Http::response([
                [
                    'display_name' => 'مدينة نصر، القاهرة، مصر',
                    'lat' => '30.0561000',
                    'lon' => '31.3300000',
                    'address' => [
                        'suburb' => 'مدينة نصر',
                        'city' => 'القاهرة',
                        'state' => 'القاهرة',
                    ],
                ],
            ]),
        ]);

        $this->getJson('/location/search?q=مدينة نصر')
            ->assertOk()
            ->assertJsonPath('results.0.city', 'القاهرة')
            ->assertJsonPath('results.0.governorate', 'القاهرة')
            ->assertJsonPath('results.0.place', 'مدينة نصر')
            ->assertJsonPath('results.0.line1', 'مدينة نصر');

        Http::assertSent(fn ($request) => str_contains($request->url(), '/search'));
    }

    public function test_reverse_lookup_rejects_missing_coordinates(): void
    {
        Http::preventStrayRequests();

        $this->getJson('/location/reverse')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['lat', 'lng']);
    }

    public function test_search_lookup_rejects_short_query(): void
    {
        Http::preventStrayRequests();

        $this->getJson('/location/search?q=ا')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['q']);
    }

    public function test_reverse_lookup_returns_422_when_provider_fails(): void
    {
        Http::preventStrayRequests();

        Http::fake([
            'nominatim.openstreetmap.org/reverse*' => Http::response(['error' => 'Unable to geocode'], 500),
        ]);

        $this->getJson('/location/reverse?lat=30.0444&lng=31.2357')
            ->assertUnprocessable()
            ->assertJsonPath('message', 'تعذر تحديد العنوان من هذا الموقع.');
    }

    public function test_reverse_lookup_matches_zone_when_place_spelling_differs(): void
    {
        Http::preventStrayRequests();
        $zone = DeliveryZone::factory()->create([
            'name_ar' => 'المعادي',
            'governorate' => 'القاهرة',
            'is_active' => true,
        ]);

        Http::fake([
            'nominatim.openstreetmap.org/reverse*' => Http::response([
                'display_name' => 'شارع 9، المعادى، القاهرة، مصر',
                'lat' => '29.9602000',
                'lon' => '31.2569000',
                'address' => [
                    'road' => 'شارع 9',
                    'suburb' => 'المعادى',
                    'state' => 'Cairo Governorate',
                ],
            ]),
        ]);

        $this->getJson('/location/reverse?lat=29.9602&lng=31.2569')
            ->assertOk()
            ->assertJsonPath('governorate', 'القاهرة')
            ->assertJsonPath('delivery_zone_id', $zone->id);
    }

    public function test_reverse_lookup_does_not_match_zone_from_another_governorate(): void
    {
        Http::preventStrayRequests();
        DeliveryZone::factory()->create([
            'name_ar' => 'المعادي',
            'governorate' => 'الجيزة',
            'is_active' => true,
        ]);

        Http::fake([
            'nominatim.openstreetmap.org/reverse*' => Http::response([
                'display_name' => 'شارع 9، المعادي، القاهرة، مصر',
                'lat' => '29.9602000',
                'lon' => '31.2569000',
                'address' => [
                    'road' => 'شارع 9',
                    'suburb' => 'المعادي',
                    'state' => 'القاهرة',
                ],
            ]),
        ]);

        $this->getJson('/location/reverse?lat=29.9602&lng=31.2569')
            ->assertOk()
            ->assertJsonPath('governorate', 'القاهرة')
            ->assertJsonPath('delivery_zone_id', null);
    }

    public function test_reverse_lookup_falls_back_to_governorate_zone(): void
    {
        Http::preventStrayRequests();
        $zone = DeliveryZone::factory()->create([
            'name_ar' => 'القاهرة',
            'governorate' => 'القاهرة',
            'is_active' => true,
        ]);

        Http::fake([
            'nominatim.openstreetmap.org/reverse*' => Http::response([
                'display_name' => 'الزمالك، القاهرة، مصر',
                'lat' => '30.0619000',
                'lon' => '31.2197000',
                'address' => [
                    'suburb' => 'الزمالك',
                    'state' => 'القاهرة',
                ],
            ]),
        ]);

        $this->getJson('/location/reverse?lat=30.0619&lng=31.2197')
            ->assertOk()
            ->assertJsonPath('governorate', 'القاهرة')
            ->assertJsonPath('delivery_zone_id', $zone->id);
    }
}
