<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\DeliveryZone;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CatalogManagementTest extends TestCase
{
    public function test_guest_is_redirected_from_business_create_page(): void
    {
        $this->get(route('admin.businesses.create'))->assertRedirect(route('login'));
    }

    public function test_agent_is_forbidden_from_creating_a_business(): void
    {
        $agent = $this->userWithRole('call_center_agent');

        $this->actingAs($agent)
            ->post(route('admin.businesses.store'), $this->businessPayload())
            ->assertForbidden();
    }

    public function test_admin_creates_a_business_with_its_own_products(): void
    {
        $admin = $this->userWithRole('admin');
        $category = BusinessCategory::factory()->create();

        $response = $this->actingAs($admin)
            ->post(route('admin.businesses.store'), $this->businessPayload([
                'business_category_id' => $category->id,
                'products' => [
                    ['category_name' => 'مشويات', 'name_ar' => 'كباب', 'price' => 90],
                    ['category_name' => 'مشروبات', 'name_ar' => 'بيبسي', 'price' => 15],
                ],
            ]));

        $business = Business::query()->where('name_ar', 'مطعم الاختبار')->first();

        $this->assertNotNull($business);
        $response->assertRedirect(route('admin.businesses.show', $business));
        $this->assertSame(2, $business->products()->count());
        $this->assertTrue($business->products()->where('name_ar', 'كباب')->exists());
        $this->assertTrue($business->productCategories()->where('name_ar', 'مشويات')->exists());
    }

    public function test_guest_is_redirected_from_business_show_page(): void
    {
        $business = Business::factory()->create();

        $this->get(route('admin.businesses.show', $business))->assertRedirect(route('login'));
    }

    public function test_admin_can_open_the_business_show_page(): void
    {
        $admin = $this->userWithRole('admin');
        $business = Business::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.businesses.show', $business))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Businesses/Show')
                ->where('business.id', $business->id)
                ->where('canManageCatalog', true));
    }

    public function test_admin_manages_categories_and_products_from_the_store_page(): void
    {
        $admin = $this->userWithRole('admin');
        $business = Business::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.businesses.product-categories.store', $business), [
                'name_ar' => 'مشروبات',
            ])
            ->assertRedirect();

        $category = $business->productCategories()->where('name_ar', 'مشروبات')->first();
        $this->assertNotNull($category);

        $this->actingAs($admin)
            ->patch(route('admin.businesses.product-categories.update', [$business, $category]), [
                'name_ar' => 'عصائر',
            ])
            ->assertRedirect();

        $this->assertSame('عصائر', $category->fresh()?->name_ar);

        $this->actingAs($admin)
            ->post(route('admin.businesses.products.store', $business), [
                'product_category_id' => $category->id,
                'name_ar' => 'مانجو',
                'price' => 30,
            ])
            ->assertRedirect();

        $product = $business->products()->where('name_ar', 'مانجو')->first();
        $this->assertNotNull($product);

        $this->actingAs($admin)
            ->from(route('admin.businesses.show', $business))
            ->delete(route('admin.businesses.product-categories.destroy', [$business, $category]))
            ->assertRedirect(route('admin.businesses.show', $business))
            ->assertSessionHas('error');

        $this->assertNotNull($category->fresh());

        $this->actingAs($admin)
            ->delete(route('admin.businesses.products.destroy', [$business, $product]))
            ->assertRedirect();

        $this->assertFalse(Product::query()->whereKey($product->id)->exists());

        $this->actingAs($admin)
            ->delete(route('admin.businesses.product-categories.destroy', [$business, $category]))
            ->assertRedirect();

        $this->assertFalse(ProductCategory::query()->whereKey($category->id)->exists());
    }

    public function test_admin_can_toggle_product_availability_from_the_store_page(): void
    {
        $admin = $this->userWithRole('admin');
        $business = Business::factory()->create();
        $product = Product::factory()->create([
            'business_id' => $business->id,
            'is_available' => true,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.businesses.show', $business))
            ->patch(route('admin.businesses.products.availability', [$business, $product]))
            ->assertRedirect(route('admin.businesses.show', $business));

        $this->assertFalse((bool) $product->fresh()?->is_available);

        $this->actingAs($admin)
            ->patch(route('admin.businesses.products.availability', [$business, $product]))
            ->assertRedirect();

        $this->assertTrue((bool) $product->fresh()?->is_available);
    }

    public function test_admin_can_upload_a_store_cover_and_a_product_image(): void
    {
        Storage::fake('public');
        $admin = $this->userWithRole('admin');
        $category = BusinessCategory::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.businesses.store'), $this->businessPayload([
                'business_category_id' => $category->id,
                'cover' => UploadedFile::fake()->image('store.jpg'),
            ]))
            ->assertRedirect();

        $business = Business::query()->where('name_ar', 'مطعم الاختبار')->first();

        $this->assertNotNull($business?->cover_path);
        Storage::disk('public')->assertExists($business->cover_path);

        $this->actingAs($admin)
            ->post(route('admin.businesses.products.store', $business), [
                'category_name' => 'مشويات',
                'name_ar' => 'كباب',
                'price' => 80,
                'image' => UploadedFile::fake()->image('kabab.jpg'),
            ])
            ->assertRedirect();

        $product = $business->products()->where('name_ar', 'كباب')->first();

        $this->assertNotNull($product?->image_path);
        Storage::disk('public')->assertExists($product->image_path);
    }

    public function test_store_cover_must_be_an_image(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)
            ->from(route('admin.businesses.create'))
            ->post(route('admin.businesses.store'), $this->businessPayload([
                'cover' => UploadedFile::fake()->create('notes.pdf', 20, 'application/pdf'),
            ]))
            ->assertRedirect(route('admin.businesses.create'))
            ->assertSessionHasErrors('cover');
    }

    public function test_admin_adds_a_product_from_the_business_page(): void
    {
        $admin = $this->userWithRole('admin');
        $business = Business::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.businesses.products.store', $business), [
                'category_name' => 'سلطات',
                'name_ar' => 'سلطة طحينة',
                'price' => 20,
            ])
            ->assertRedirect();

        $this->assertTrue($business->products()->where('name_ar', 'سلطة طحينة')->exists());
    }

    public function test_business_manager_cannot_add_a_product_from_another_business_page(): void
    {
        $manager = $this->userWithRole('business_manager');
        $other = Business::factory()->create();

        $this->actingAs($manager)
            ->post(route('admin.businesses.products.store', $other), [
                'category_name' => 'مشويات',
                'name_ar' => 'كباب',
                'price' => 70,
            ])
            ->assertForbidden();
    }

    public function test_admin_creates_a_business_with_branch_and_hours(): void
    {
        $admin = $this->userWithRole('admin');
        $category = BusinessCategory::factory()->create();
        $zone = DeliveryZone::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.businesses.store'), $this->businessPayload([
                'business_category_id' => $category->id,
                'delivery_zone_id' => $zone->id,
                'branch_name' => 'الفرع الرئيسي',
                'branch_address' => 'شارع التحرير',
            ]))
            ->assertRedirect();

        $business = Business::query()->where('name_ar', 'مطعم الاختبار')->first();

        $this->assertNotNull($business);
        $this->assertSame(1, $business->branches()->count());
        $this->assertSame('شارع التحرير', $business->branches()->first()?->address);
        $this->assertSame(7, $business->hours()->count());
        $this->assertFalse((bool) $business->hours()->first()?->is_closed);
    }

    public function test_admin_cannot_create_a_business_without_a_name(): void
    {
        $admin = $this->userWithRole('admin');
        $category = BusinessCategory::factory()->create();

        $this->actingAs($admin)
            ->from(route('admin.businesses.create'))
            ->post(route('admin.businesses.store'), $this->businessPayload([
                'business_category_id' => $category->id,
                'name_ar' => '',
            ]))
            ->assertRedirect(route('admin.businesses.create'))
            ->assertSessionHasErrors('name_ar');
    }

    public function test_admin_creates_a_product_with_a_new_category_name(): void
    {
        $admin = $this->userWithRole('admin');
        $business = Business::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.products.store'), [
                'business_id' => $business->id,
                'category_name' => 'مشويات',
                'name_ar' => 'كباب حلة',
                'price' => 85,
                'is_available' => true,
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('product_categories', [
            'business_id' => $business->id,
            'name_ar' => 'مشويات',
        ]);
        $this->assertDatabaseHas('products', [
            'business_id' => $business->id,
            'name_ar' => 'كباب حلة',
            'price' => 85,
        ]);
    }

    public function test_product_create_fails_without_a_category(): void
    {
        $admin = $this->userWithRole('admin');
        $business = Business::factory()->create();

        $this->actingAs($admin)
            ->from(route('admin.products.index'))
            ->post(route('admin.products.store'), [
                'business_id' => $business->id,
                'name_ar' => 'كباب حلة',
                'price' => 85,
            ])
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHasErrors('product_category_id');
    }

    public function test_business_manager_cannot_add_a_product_to_another_business(): void
    {
        $manager = $this->userWithRole('business_manager');
        $other = Business::factory()->create();

        $this->actingAs($manager)
            ->post(route('admin.products.store'), [
                'business_id' => $other->id,
                'category_name' => 'مشويات',
                'name_ar' => 'كباب',
                'price' => 70,
            ])
            ->assertForbidden();

        $this->assertFalse(Product::query()->where('business_id', $other->id)->exists());
    }

    public function test_business_manager_can_add_a_product_to_their_business(): void
    {
        $manager = $this->userWithRole('business_manager');
        $owned = Business::factory()->create(['manager_user_id' => $manager->id]);

        $this->actingAs($manager)
            ->post(route('admin.products.store'), [
                'business_id' => $owned->id,
                'category_name' => 'مشويات',
                'name_ar' => 'كباب',
                'price' => 70,
            ])
            ->assertRedirect();

        $this->assertTrue(Product::query()->where('business_id', $owned->id)->where('name_ar', 'كباب')->exists());
    }

    public function test_admin_creates_a_delivery_zone_with_an_initial_fee(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)
            ->post(route('admin.zones.store'), [
                'name_ar' => 'المعادي',
                'name_en' => 'Maadi',
                'governorate' => 'القاهرة',
                'min_order' => 0,
                'fee' => 25,
                'is_active' => true,
            ])
            ->assertRedirect();

        $zone = DeliveryZone::query()->where('name_ar', 'المعادي')->first();

        $this->assertNotNull($zone);
        $this->assertSame('القاهرة', $zone->governorate);
        $this->assertSame('25.00', $zone->feeRules()->first()?->fee);
    }

    public function test_admin_cannot_create_a_delivery_zone_without_governorate(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)
            ->from(route('admin.zones.index'))
            ->post(route('admin.zones.store'), [
                'name_ar' => 'الدقي',
                'fee' => 20,
            ])
            ->assertRedirect(route('admin.zones.index'))
            ->assertSessionHasErrors(['governorate']);
    }

    public function test_admin_can_create_a_product_using_an_existing_category(): void
    {
        $admin = $this->userWithRole('admin');
        $business = Business::factory()->create();
        $category = ProductCategory::factory()->create([
            'business_id' => $business->id,
            'name_ar' => 'مشروبات',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.products.store'), [
                'business_id' => $business->id,
                'product_category_id' => $category->id,
                'name_ar' => 'بيبسي',
                'price' => 15,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('products', [
            'product_category_id' => $category->id,
            'name_ar' => 'بيبسي',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function businessPayload(array $overrides = []): array
    {
        return [
            'business_category_id' => $overrides['business_category_id'] ?? BusinessCategory::factory()->create()->id,
            'name_ar' => 'مطعم الاختبار',
            'min_order_amount' => 40,
            'delivery_time_min' => 25,
            'delivery_time_max' => 45,
            'is_active' => true,
            'accepts_orders' => true,
            'hours' => collect(range(0, 6))->map(fn (int $day): array => [
                'day_of_week' => $day,
                'opens_at' => '10:00',
                'closes_at' => '23:30',
                'is_closed' => false,
            ])->all(),
            ...$overrides,
        ];
    }
}
