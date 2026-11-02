<?php

namespace Database\Seeders;

use App\Enums\AddressLabel;
use App\Enums\CouponType;
use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\Coupon;
use App\Models\CustomerAddress;
use App\Models\DeliveryFeeRule;
use App\Models\DeliveryZone;
use App\Models\DriverProfile;
use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoCatalogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PlatformSetting::setValue('brand', [
            'name_ar' => 'توسيلا',
            'name_en' => 'Towseela',
            'tagline' => 'توصيل احترافي',
            'support_phone' => '19900',
            'support_email' => 'support@towseela.test',
            'whatsapp' => '01000000000',
        ]);

        PlatformSetting::setValue('operations', [
            'currency' => 'EGP',
            'currency_label' => 'ج.م',
            'allow_mixed_business_cart' => false,
            'require_proof_of_delivery_note' => false,
            'driver_active_order_limit' => 3,
            'overdue_minutes' => 90,
        ]);

        PlatformSetting::setValue('map', [
            'default_lat' => 30.0444,
            'default_lng' => 31.2357,
            'default_city' => 'القاهرة',
        ]);

        PlatformSetting::setValue('features', [
            'customer_registration_enabled' => true,
            'maintenance_mode' => false,
            'maintenance_message' => 'المنصة تحت الصيانة مؤقتاً. نعود قريباً.',
        ]);

        $zones = collect([
            ['name_ar' => 'مدينة نصر', 'name_en' => 'Nasr City', 'governorate' => 'القاهرة', 'aliases' => ['nasr city']],
            ['name_ar' => 'المعادي', 'name_en' => 'Maadi', 'governorate' => 'القاهرة', 'aliases' => ['المعادى', 'Cairo Maadi']],
            ['name_ar' => 'الزمالك', 'name_en' => 'Zamalek', 'governorate' => 'القاهرة', 'aliases' => ['zamalek']],
            ['name_ar' => 'التجمع الخامس', 'name_en' => 'New Cairo', 'governorate' => 'القاهرة', 'aliases' => ['التجمع', 'new cairo']],
        ])->map(function (array $zone) {
            return DeliveryZone::query()->updateOrCreate(
                ['name_ar' => $zone['name_ar']],
                [...$zone, 'is_active' => true],
            );
        });

        foreach ($zones as $index => $zone) {
            DeliveryFeeRule::query()->updateOrCreate(
                ['delivery_zone_id' => $zone->id, 'min_order' => 0],
                ['fee' => 20 + ($index * 5), 'is_active' => true],
            );

            DeliveryFeeRule::query()->updateOrCreate(
                ['delivery_zone_id' => $zone->id, 'min_order' => 200],
                ['fee' => 10 + ($index * 3), 'is_active' => true],
            );
        }

        $categories = [
            ['name_ar' => 'مطاعم', 'slug' => 'restaurants', 'icon' => 'utensils'],
            ['name_ar' => 'كافيهات', 'slug' => 'cafes', 'icon' => 'coffee'],
            ['name_ar' => 'سوبرماركت', 'slug' => 'supermarkets', 'icon' => 'basket'],
            ['name_ar' => 'صيدليات', 'slug' => 'pharmacies', 'icon' => 'plus'],
        ];

        $categoryModels = collect($categories)->map(function (array $category, int $index) {
            return BusinessCategory::query()->updateOrCreate(
                ['slug' => $category['slug']],
                [...$category, 'sort_order' => $index + 1, 'is_active' => true],
            );
        })->keyBy('slug');

        $manager = User::query()->where('email', 'manager@towseela.test')->first();
        $customer = User::query()->where('email', 'customer@towseela.test')->first();
        $driver = User::query()->where('email', 'driver@towseela.test')->first();

        if ($driver) {
            DriverProfile::query()->updateOrCreate(
                ['user_id' => $driver->id],
                [
                    'vehicle_type' => 'موتوسيكل',
                    'license_number' => 'DRV-1001',
                    'is_available' => true,
                    'status' => 'available',
                ],
            );
        }

        $businesses = [
            [
                'slug' => 'beit-el-shawerma',
                'name_ar' => 'بيت الشاورما',
                'category' => 'restaurants',
                'description' => 'شاورما ودجاج مشوي على الأصول الدمشقية.',
                'products' => [
                    ['شاورما دجاج', 85, 'سندويتشات'],
                    ['شاورما لحم', 110, 'سندويتشات'],
                    ['وجبة عائلية', 260, 'وجبات'],
                ],
            ],
            [
                'slug' => 'nile-grill',
                'name_ar' => 'نيل جريل',
                'category' => 'restaurants',
                'description' => 'مشويات ومأكولات مصرية سريعة.',
                'products' => [
                    ['كفتة مشوية', 95, 'مشويات'],
                    ['ريش ضاني', 180, 'مشويات'],
                    ['سلطة طحينة', 25, 'مقبلات'],
                ],
            ],
            [
                'slug' => 'qahwet-el-midan',
                'name_ar' => 'قهوة الميدان',
                'category' => 'cafes',
                'description' => 'قهوة عربية وحلويات شرقية.',
                'products' => [
                    ['قهوة تركي', 35, 'مشروبات'],
                    ['كنافة', 55, 'حلويات'],
                    ['شاي بالنعناع', 20, 'مشروبات'],
                ],
            ],
            [
                'slug' => 'souq-el-beit',
                'name_ar' => 'سوق البيت',
                'category' => 'supermarkets',
                'description' => 'بقالة يومية ومنتجات غذائية.',
                'products' => [
                    ['أرز مصري 1كجم', 42, 'جاف'],
                    ['زيت عباد 1لتر', 78, 'جاف'],
                    ['حليب كامل الدسم', 36, 'ألبان'],
                ],
            ],
            [
                'slug' => 'sehetak',
                'name_ar' => 'صيدلية صحتك',
                'category' => 'pharmacies',
                'description' => 'أدوية ومستلزمات طبية غير وصفيّة.',
                'products' => [
                    ['فيتامين سي', 48, 'فيتامينات'],
                    ['كمامات', 15, 'مستلزمات'],
                    ['مطهر يدوي', 30, 'مستلزمات'],
                ],
            ],
        ];

        foreach ($businesses as $index => $data) {
            $business = Business::query()->updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'business_category_id' => $categoryModels[$data['category']]->id,
                    'manager_user_id' => $index === 0 ? $manager?->id : null,
                    'name_ar' => $data['name_ar'],
                    'description' => $data['description'],
                    'phone' => '02'.str_pad((string) (3300000 + $index), 7, '0', STR_PAD_LEFT),
                    'is_active' => true,
                    'accepts_orders' => true,
                    'min_order_amount' => 30,
                    'delivery_time_min' => 25,
                    'delivery_time_max' => 50,
                ],
            );

            $branch = $business->branches()->updateOrCreate(
                ['name_ar' => 'الفرع الرئيسي'],
                [
                    'delivery_zone_id' => $zones[$index % $zones->count()]->id,
                    'address' => 'شارع التحرير، '.$zones[$index % $zones->count()]->name_ar,
                    'is_active' => true,
                ],
            );

            foreach (range(0, 6) as $day) {
                $business->hours()->updateOrCreate(
                    ['day_of_week' => $day, 'business_branch_id' => null],
                    [
                        'opens_at' => '10:00:00',
                        'closes_at' => '23:30:00',
                        'is_closed' => false,
                    ],
                );
            }

            $grouped = collect($data['products'])->groupBy(2);

            foreach ($grouped as $categoryName => $products) {
                $category = $business->productCategories()->updateOrCreate(
                    ['name_ar' => $categoryName],
                    ['is_active' => true, 'sort_order' => 1],
                );

                foreach ($products as $sort => $product) {
                    $created = $business->products()->updateOrCreate(
                        ['name_ar' => $product[0]],
                        [
                            'product_category_id' => $category->id,
                            'price' => $product[1],
                            'is_available' => true,
                            'is_active' => true,
                            'sort_order' => $sort,
                            'description' => 'منتج متاح للتوصيل عبر توسيلا.',
                        ],
                    );

                    if (Str::contains($created->name_ar, 'شاورما')) {
                        $option = $created->options()->updateOrCreate(
                            ['name_ar' => 'إضافات'],
                            ['is_required' => false, 'max_select' => 2],
                        );

                        $option->values()->updateOrCreate(['name_ar' => 'ثومية'], ['price_delta' => 5]);
                        $option->values()->updateOrCreate(['name_ar' => 'مخلل'], ['price_delta' => 3]);
                    }
                }
            }

            unset($branch);
        }

        Coupon::query()->updateOrCreate(
            ['code' => 'TOWSEELA10'],
            [
                'name_ar' => 'خصم الترحيب',
                'type' => CouponType::Percent,
                'value' => 10,
                'min_order' => 80,
                'max_discount' => 40,
                'usage_limit' => 500,
                'used_count' => 0,
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addMonths(3),
                'is_active' => true,
            ],
        );

        if ($customer) {
            CustomerAddress::query()->updateOrCreate(
                ['user_id' => $customer->id, 'line1' => 'عمارة 12، شارع عباس العقاد'],
                [
                    'label' => AddressLabel::Home,
                    'city' => 'القاهرة',
                    'delivery_zone_id' => $zones->first()->id,
                    'notes' => 'الدور الثالث، الشقة يمين المصعد',
                    'is_default' => true,
                ],
            );
        }
    }
}
