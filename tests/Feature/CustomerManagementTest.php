<?php

namespace Tests\Feature;

use App\Models\Order;
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

    public function test_admin_can_block_customer_temporarily_and_unblock(): void
    {
        $admin = $this->userWithRole('admin');
        $customer = User::factory()->create(['phone' => '01012121212']);
        $customer->assignRole('customer');

        $this->actingAs($admin)
            ->post(route('admin.customers.block', $customer), [
                'duration' => '7_days',
                'reason' => 'إساءة استخدام',
            ])
            ->assertRedirect();

        $customer->refresh();

        $this->assertFalse($customer->is_active);
        $this->assertNotNull($customer->blocked_until);
        $this->assertTrue($customer->blocked_until->isFuture());
        $this->assertSame('إساءة استخدام', $customer->block_reason);

        $this->actingAs($admin)
            ->post(route('admin.customers.unblock', $customer))
            ->assertRedirect();

        $customer->refresh();

        $this->assertTrue($customer->is_active);
        $this->assertNull($customer->blocked_until);
        $this->assertNull($customer->block_reason);
    }

    public function test_admin_can_block_customer_permanently(): void
    {
        $admin = $this->userWithRole('admin');
        $customer = User::factory()->create(['phone' => '01013131313']);
        $customer->assignRole('customer');

        $this->actingAs($admin)
            ->post(route('admin.customers.block', $customer), [
                'duration' => 'permanent',
            ])
            ->assertRedirect();

        $customer->refresh();

        $this->assertFalse($customer->is_active);
        $this->assertNull($customer->blocked_until);
        $this->assertTrue($customer->isPermanentlyBlocked());
    }

    public function test_blocked_customer_cannot_login_and_active_session_is_logged_out(): void
    {
        $admin = $this->userWithRole('admin');
        $customer = User::factory()->create([
            'phone' => '01014141414',
            'password' => 'Password1!',
        ]);
        $customer->assignRole('customer');

        $this->actingAs($customer)
            ->get(route('customer.profile'))
            ->assertOk();

        $this->actingAs($admin)
            ->post(route('admin.customers.block', $customer), [
                'duration' => 'permanent',
            ])
            ->assertRedirect();

        $this->actingAs($customer->fresh())
            ->get(route('customer.profile'))
            ->assertRedirect(route('login'));

        $this->post(route('login'), [
            'login' => '01014141414',
            'password' => 'Password1!',
        ])->assertSessionHasErrors('login');
    }

    public function test_expired_temporary_block_is_cleared_on_login(): void
    {
        $this->seedRoles();

        $customer = User::factory()->create([
            'phone' => '01015151515',
            'password' => 'Password1!',
            'is_active' => false,
            'blocked_until' => now()->subHour(),
            'block_reason' => 'مؤقت',
        ]);
        $customer->assignRole('customer');

        $this->post(route('login'), [
            'login' => '01015151515',
            'password' => 'Password1!',
        ])->assertRedirect(route('customer.home'));

        $customer->refresh();

        $this->assertTrue($customer->is_active);
        $this->assertNull($customer->blocked_until);
        $this->assertNull($customer->block_reason);
    }

    public function test_admin_can_delete_customer_without_orders(): void
    {
        $admin = $this->userWithRole('admin');
        $customer = User::factory()->create(['phone' => '01016161616']);
        $customer->assignRole('customer');

        $this->actingAs($admin)
            ->delete(route('admin.customers.destroy', $customer))
            ->assertRedirect(route('admin.customers.index'));

        $this->assertDatabaseMissing('users', ['id' => $customer->id]);
    }

    public function test_admin_cannot_delete_customer_with_orders(): void
    {
        $admin = $this->userWithRole('admin');
        $customer = User::factory()->create(['phone' => '01017171717']);
        $customer->assignRole('customer');

        Order::factory()->create(['user_id' => $customer->id]);

        $this->actingAs($admin)
            ->from(route('admin.customers.show', $customer))
            ->delete(route('admin.customers.destroy', $customer))
            ->assertRedirect(route('admin.customers.show', $customer))
            ->assertSessionHasErrors('customer');

        $this->assertDatabaseHas('users', ['id' => $customer->id]);
    }

    public function test_call_center_agent_cannot_block_customer(): void
    {
        $agent = $this->userWithRole('call_center_agent');
        $customer = User::factory()->create(['phone' => '01018181818']);
        $customer->assignRole('customer');

        $this->actingAs($agent)
            ->post(route('admin.customers.block', $customer), [
                'duration' => 'permanent',
            ])
            ->assertForbidden();
    }
}
