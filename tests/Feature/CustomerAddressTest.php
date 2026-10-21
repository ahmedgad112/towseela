<?php

namespace Tests\Feature;

use App\Models\CustomerAddress;
use App\Models\DeliveryZone;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CustomerAddressTest extends TestCase
{
    public function test_guest_is_redirected_from_addresses(): void
    {
        $this->get('/addresses')->assertRedirect(route('login'));
        $this->post('/addresses', [])->assertRedirect(route('login'));
    }

    public function test_customer_can_open_addresses_page(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer)
            ->get(route('customer.addresses.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Customer/Addresses'));
    }

    public function test_customer_can_save_typed_address_with_map_coordinates(): void
    {
        $customer = $this->customer();
        $zone = DeliveryZone::factory()->create(['is_active' => true]);

        $this->actingAs($customer)
            ->post(route('customer.addresses.store'), [
                'label' => 'home',
                'line1' => 'شارع التحرير، المعادي',
                'city' => 'القاهرة',
                'delivery_zone_id' => $zone->id,
                'notes' => 'الدور الثالث',
                'lat' => 29.9602,
                'lng' => 31.2569,
                'is_default' => true,
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'تم حفظ العنوان.');

        $address = CustomerAddress::query()->where('user_id', $customer->id)->first();

        $this->assertNotNull($address);
        $this->assertSame('شارع التحرير، المعادي', $address->line1);
        $this->assertSame('القاهرة', $address->city);
        $this->assertSame($zone->id, $address->delivery_zone_id);
        $this->assertEqualsWithDelta(29.9602, (float) $address->lat, 0.0001);
        $this->assertEqualsWithDelta(31.2569, (float) $address->lng, 0.0001);
        $this->assertTrue($address->is_default);
    }

    public function test_customer_can_save_typed_address_without_coordinates(): void
    {
        $customer = $this->customer();
        $zone = DeliveryZone::factory()->create(['is_active' => true]);

        $this->actingAs($customer)
            ->post(route('customer.addresses.store'), [
                'label' => 'work',
                'line1' => 'مكتب الشركة، التجمع الخامس',
                'city' => 'القاهرة',
                'delivery_zone_id' => $zone->id,
            ])
            ->assertRedirect();

        $address = CustomerAddress::query()->where('user_id', $customer->id)->first();

        $this->assertNotNull($address);
        $this->assertSame('مكتب الشركة، التجمع الخامس', $address->line1);
        $this->assertNull($address->lat);
        $this->assertNull($address->lng);
    }

    public function test_address_store_rejects_missing_required_fields(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer)
            ->from(route('customer.addresses.index'))
            ->post(route('customer.addresses.store'), [])
            ->assertRedirect(route('customer.addresses.index'))
            ->assertSessionHasErrors(['label', 'line1', 'city', 'delivery_zone_id']);
    }

    public function test_address_store_rejects_invalid_coordinates(): void
    {
        $customer = $this->customer();
        $zone = DeliveryZone::factory()->create(['is_active' => true]);

        $this->actingAs($customer)
            ->from(route('customer.addresses.index'))
            ->post(route('customer.addresses.store'), [
                'label' => 'home',
                'line1' => 'عنوان تجريبي',
                'city' => 'القاهرة',
                'delivery_zone_id' => $zone->id,
                'lat' => 120,
                'lng' => 31.2357,
            ])
            ->assertRedirect(route('customer.addresses.index'))
            ->assertSessionHasErrors(['lat']);
    }

    private function customer(): User
    {
        $this->seedRoles();

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        return $customer;
    }
}
