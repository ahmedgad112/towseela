<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
{
    public function test_admin_can_search_customers_by_name_and_phone(): void
    {
        $admin = $this->userWithRole('admin');

        $match = User::factory()->create([
            'name' => 'محمود الهاتف',
            'phone' => '01011112222',
        ]);
        $match->assignRole('customer');

        User::factory()->create([
            'name' => 'عميل آخر',
            'phone' => '01099998888',
        ])->assignRole('customer');

        $this->actingAs($admin)
            ->get(route('admin.customers.index', ['q' => '0101111']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Customers/Index')
                ->has('customers.data', 1)
                ->where('customers.data.0.id', $match->id));

        $this->actingAs($admin)
            ->get(route('admin.customers.index', ['q' => 'محمود']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('customers.data', 1)
                ->where('customers.data.0.phone', '01011112222'));
    }

    public function test_admin_can_create_a_customer(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)
            ->post(route('admin.customers.store'), [
                'name' => 'عميل جديد',
                'phone' => '01033334444',
                'email' => 'new-customer@towseela.test',
                'password' => 'Password1!',
            ])
            ->assertRedirect();

        $customer = User::query()->where('phone', '01033334444')->first();

        $this->assertNotNull($customer);
        $this->assertTrue($customer->hasRole('customer'));
        $this->assertTrue(Hash::check('Password1!', $customer->password));
    }

    public function test_call_center_agent_can_create_customer_without_email(): void
    {
        $agent = $this->userWithRole('call_center_agent');

        $this->actingAs($agent)
            ->postJson(route('call-center.customers.store'), [
                'name' => 'عميل اتصال',
                'phone' => '01055556666',
            ])
            ->assertCreated()
            ->assertJsonPath('customer.phone', '01055556666');

        $customer = User::query()->where('phone', '01055556666')->first();

        $this->assertNotNull($customer);
        $this->assertTrue($customer->hasRole('customer'));
        $this->assertSame('01055556666@phone.towseela.local', $customer->email);
    }

    public function test_call_center_can_search_customers_by_phone_or_name(): void
    {
        $agent = $this->userWithRole('call_center_agent');

        $customer = User::factory()->create([
            'name' => 'سارة البحث',
            'phone' => '01077778888',
        ]);
        $customer->assignRole('customer');

        $this->actingAs($agent)
            ->getJson(route('call-center.customers.search', ['q' => '0107777']))
            ->assertOk()
            ->assertJsonPath('customers.0.id', $customer->id);

        $this->actingAs($agent)
            ->getJson(route('call-center.customers.search', ['q' => 'سارة']))
            ->assertOk()
            ->assertJsonPath('customers.0.phone', '01077778888');
    }
}
