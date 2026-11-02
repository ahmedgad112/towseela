<?php

namespace Database\Seeders;

use App\Models\DeliveryFeeRule;
use App\Models\DeliveryZone;
use Illuminate\Database\Seeder;

class DeliveryZoneSeeder extends Seeder
{
    /**
     * Seed major delivery zones for every Egyptian governorate.
     */
    public function run(): void
    {
        foreach ($this->zonesByGovernorate() as $governorate => $zones) {
            foreach ($zones as $index => $zone) {
                $model = DeliveryZone::query()->updateOrCreate(
                    [
                        'name_ar' => $zone['name_ar'],
                        'governorate' => $governorate,
                    ],
                    [
                        'name_en' => $zone['name_en'] ?? null,
                        'aliases' => $zone['aliases'] ?? [],
                        'is_active' => true,
                    ],
                );

                DeliveryFeeRule::query()->updateOrCreate(
                    [
                        'delivery_zone_id' => $model->id,
                        'min_order' => 0,
                    ],
                    [
                        'fee' => $zone['fee'] ?? $this->defaultFee($governorate, $index),
                        'is_active' => true,
                    ],
                );
            }
        }
    }

    /**
     * @return array<string, list<array{name_ar: string, name_en?: string, aliases?: list<string>, fee?: int|float}>>
     */
    private function zonesByGovernorate(): array
    {
        return [
            'القاهرة' => [
                ['name_ar' => 'مدينة نصر', 'name_en' => 'Nasr City', 'aliases' => ['nasr city'], 'fee' => 25],
                ['name_ar' => 'المعادي', 'name_en' => 'Maadi', 'aliases' => ['المعادى', 'maadi'], 'fee' => 25],
                ['name_ar' => 'الزمالك', 'name_en' => 'Zamalek', 'aliases' => ['zamalek'], 'fee' => 25],
                ['name_ar' => 'التجمع الخامس', 'name_en' => 'New Cairo', 'aliases' => ['التجمع', 'new cairo'], 'fee' => 30],
                ['name_ar' => 'مصر الجديدة', 'name_en' => 'Heliopolis', 'aliases' => ['heliopolis'], 'fee' => 25],
                ['name_ar' => 'شبرا', 'name_en' => 'Shubra', 'aliases' => ['shubra'], 'fee' => 25],
                ['name_ar' => 'العباسية', 'name_en' => 'Abbasia', 'aliases' => ['abbasia'], 'fee' => 25],
                ['name_ar' => 'وسط البلد', 'name_en' => 'Downtown', 'aliases' => ['downtown'], 'fee' => 25],
                ['name_ar' => 'المقطم', 'name_en' => 'Mokattam', 'aliases' => ['mokattam'], 'fee' => 30],
                ['name_ar' => 'حلوان', 'name_en' => 'Helwan', 'aliases' => ['helwan'], 'fee' => 30],
                ['name_ar' => 'عين شمس', 'name_en' => 'Ain Shams', 'aliases' => ['ain shams'], 'fee' => 25],
                ['name_ar' => 'المطرية', 'name_en' => 'Matareya', 'aliases' => ['matareya'], 'fee' => 25],
            ],
            'الجيزة' => [
                ['name_ar' => 'الدقي', 'name_en' => 'Dokki', 'fee' => 25],
                ['name_ar' => 'المهندسين', 'name_en' => 'Mohandessin', 'fee' => 25],
                ['name_ar' => 'العجوزة', 'name_en' => 'Agouza', 'fee' => 25],
                ['name_ar' => 'الهرم', 'name_en' => 'Haram', 'fee' => 25],
                ['name_ar' => 'فيصل', 'name_en' => 'Faisal', 'fee' => 25],
                ['name_ar' => '6 أكتوبر', 'name_en' => '6th of October', 'aliases' => ['أكتوبر', 'october'], 'fee' => 35],
                ['name_ar' => 'الشيخ زايد', 'name_en' => 'Sheikh Zayed', 'fee' => 35],
                ['name_ar' => 'حدائق الأهرام', 'name_en' => 'Hadayek Al Ahram', 'fee' => 30],
                ['name_ar' => 'العمرانية', 'name_en' => 'Omraneya', 'fee' => 25],
                ['name_ar' => 'الوراق', 'name_en' => 'Warraq', 'fee' => 30],
            ],
            'الإسكندرية' => [
                ['name_ar' => 'سموحة', 'name_en' => 'Smouha', 'fee' => 25],
                ['name_ar' => 'ستانلي', 'name_en' => 'Stanley', 'fee' => 25],
                ['name_ar' => 'سيدي جابر', 'name_en' => 'Sidi Gaber', 'fee' => 25],
                ['name_ar' => 'العجمي', 'name_en' => 'Agami', 'aliases' => ['العجمى'], 'fee' => 30],
                ['name_ar' => 'ميامي', 'name_en' => 'Miami', 'fee' => 25],
                ['name_ar' => 'المنتزه', 'name_en' => 'Montaza', 'fee' => 30],
                ['name_ar' => 'محرم بك', 'name_en' => 'Moharam Bek', 'fee' => 25],
                ['name_ar' => 'العطارين', 'name_en' => 'Attarin', 'fee' => 25],
                ['name_ar' => 'برج العرب', 'name_en' => 'Borg El Arab', 'fee' => 40],
            ],
            'القليوبية' => [
                ['name_ar' => 'بنها', 'name_en' => 'Banha', 'fee' => 30],
                ['name_ar' => 'شبرا الخيمة', 'name_en' => 'Shubra El Kheima', 'fee' => 25],
                ['name_ar' => 'القناطر الخيرية', 'name_en' => 'Qanater', 'fee' => 30],
                ['name_ar' => 'الخانكة', 'name_en' => 'Khanka', 'fee' => 30],
                ['name_ar' => 'طوخ', 'name_en' => 'Tookh', 'fee' => 30],
                ['name_ar' => 'قليوب', 'name_en' => 'Qalyub', 'fee' => 25],
            ],
            'الشرقية' => [
                ['name_ar' => 'الزقازيق', 'name_en' => 'Zagazig', 'fee' => 30],
                ['name_ar' => 'العاشر من رمضان', 'name_en' => '10th of Ramadan', 'fee' => 35],
                ['name_ar' => 'بلبيس', 'name_en' => 'Belbeis', 'fee' => 30],
                ['name_ar' => 'منيا القمح', 'name_en' => 'Minya Al Qamh', 'fee' => 30],
                ['name_ar' => 'فاقوس', 'name_en' => 'Faqous', 'fee' => 35],
                ['name_ar' => 'أبو حماد', 'name_en' => 'Abu Hammad', 'fee' => 30],
            ],
            'الدقهلية' => [
                ['name_ar' => 'المنصورة', 'name_en' => 'Mansoura', 'fee' => 30],
                ['name_ar' => 'طلخا', 'name_en' => 'Talkha', 'fee' => 30],
                ['name_ar' => 'ميت غمر', 'name_en' => 'Mit Ghamr', 'fee' => 30],
                ['name_ar' => 'بلقاس', 'name_en' => 'Belqas', 'fee' => 35],
                ['name_ar' => 'دكرنس', 'name_en' => 'Dikirnis', 'fee' => 30],
                ['name_ar' => 'السنبلاوين', 'name_en' => 'Sinbillawin', 'fee' => 30],
            ],
            'الغربية' => [
                ['name_ar' => 'طنطا', 'name_en' => 'Tanta', 'fee' => 30],
                ['name_ar' => 'المحلة الكبرى', 'name_en' => 'El Mahalla', 'fee' => 30],
                ['name_ar' => 'كفر الزيات', 'name_en' => 'Kafr El Zayat', 'fee' => 30],
                ['name_ar' => 'زفتى', 'name_en' => 'Zefta', 'fee' => 30],
                ['name_ar' => 'سمنود', 'name_en' => 'Samannoud', 'fee' => 30],
            ],
            'المنوفية' => [
                ['name_ar' => 'شبين الكوم', 'name_en' => 'Shebin El Kom', 'fee' => 30],
                ['name_ar' => 'منوف', 'name_en' => 'Menouf', 'fee' => 30],
                ['name_ar' => 'أشمون', 'name_en' => 'Ashmoun', 'fee' => 30],
                ['name_ar' => 'قويسنا', 'name_en' => 'Quesna', 'fee' => 30],
                ['name_ar' => 'بركة السبع', 'name_en' => 'Berket El Sabaa', 'fee' => 30],
            ],
            'البحيرة' => [
                ['name_ar' => 'دمنهور', 'name_en' => 'Damanhour', 'fee' => 30],
                ['name_ar' => 'كفر الدوار', 'name_en' => 'Kafr El Dawar', 'fee' => 30],
                ['name_ar' => 'رشيد', 'name_en' => 'Rosetta', 'fee' => 35],
                ['name_ar' => 'إدكو', 'name_en' => 'Edku', 'fee' => 35],
                ['name_ar' => 'أبو حمص', 'name_en' => 'Abu Hommos', 'fee' => 30],
                ['name_ar' => 'حوش عيسى', 'name_en' => 'Hosh Essa', 'fee' => 30],
            ],
            'كفر الشيخ' => [
                ['name_ar' => 'كفر الشيخ', 'name_en' => 'Kafr El Sheikh', 'fee' => 30],
                ['name_ar' => 'دسوق', 'name_en' => 'Desouk', 'fee' => 30],
                ['name_ar' => 'بلطيم', 'name_en' => 'Baltim', 'fee' => 35],
                ['name_ar' => 'سيدي سالم', 'name_en' => 'Sidi Salem', 'fee' => 30],
                ['name_ar' => 'قلين', 'name_en' => 'Qellin', 'fee' => 30],
            ],
            'دمياط' => [
                ['name_ar' => 'دمياط', 'name_en' => 'Damietta', 'fee' => 30],
                ['name_ar' => 'دمياط الجديدة', 'name_en' => 'New Damietta', 'fee' => 30],
                ['name_ar' => 'فارسكور', 'name_en' => 'Faraskour', 'fee' => 30],
                ['name_ar' => 'الزرقا', 'name_en' => 'Zarqa', 'fee' => 30],
                ['name_ar' => 'كفر سعد', 'name_en' => 'Kafr Saad', 'fee' => 30],
            ],
            'بورسعيد' => [
                ['name_ar' => 'حي الشرق', 'name_en' => 'Sharq', 'fee' => 25],
                ['name_ar' => 'حي العرب', 'name_en' => 'Arab', 'fee' => 25],
                ['name_ar' => 'حي المناخ', 'name_en' => 'Manakh', 'fee' => 25],
                ['name_ar' => 'حي الضواحي', 'name_en' => 'Dawahi', 'fee' => 25],
                ['name_ar' => 'بورفؤاد', 'name_en' => 'Port Fouad', 'fee' => 30],
            ],
            'الإسماعيلية' => [
                ['name_ar' => 'الإسماعيلية', 'name_en' => 'Ismailia', 'fee' => 30],
                ['name_ar' => 'فايد', 'name_en' => 'Fayed', 'fee' => 30],
                ['name_ar' => 'القنطرة شرق', 'name_en' => 'Qantara East', 'fee' => 35],
                ['name_ar' => 'التل الكبير', 'name_en' => 'El Tal El Kebir', 'fee' => 30],
                ['name_ar' => 'أبو صوير', 'name_en' => 'Abu Suwir', 'fee' => 30],
            ],
            'السويس' => [
                ['name_ar' => 'السويس', 'name_en' => 'Suez', 'fee' => 30],
                ['name_ar' => 'الأربعين', 'name_en' => 'Arbaeen', 'fee' => 30],
                ['name_ar' => 'عتاقة', 'name_en' => 'Ataqa', 'fee' => 30],
                ['name_ar' => 'الجناين', 'name_en' => 'Ganayen', 'fee' => 30],
                ['name_ar' => 'فيصل', 'name_en' => 'Faisal', 'fee' => 30],
            ],
            'شمال سيناء' => [
                ['name_ar' => 'العريش', 'name_en' => 'Arish', 'fee' => 40],
                ['name_ar' => 'الشيخ زويد', 'name_en' => 'Sheikh Zuweid', 'fee' => 45],
                ['name_ar' => 'رفح', 'name_en' => 'Rafah', 'fee' => 45],
                ['name_ar' => 'بئر العبد', 'name_en' => 'Bir El Abd', 'fee' => 40],
            ],
            'جنوب سيناء' => [
                ['name_ar' => 'شرم الشيخ', 'name_en' => 'Sharm El Sheikh', 'fee' => 45],
                ['name_ar' => 'دهب', 'name_en' => 'Dahab', 'fee' => 45],
                ['name_ar' => 'نويبع', 'name_en' => 'Nuweiba', 'fee' => 45],
                ['name_ar' => 'طور سيناء', 'name_en' => 'El Tor', 'fee' => 40],
                ['name_ar' => 'أبو رديس', 'name_en' => 'Abu Redis', 'fee' => 45],
            ],
            'الفيوم' => [
                ['name_ar' => 'الفيوم', 'name_en' => 'Fayoum', 'fee' => 30],
                ['name_ar' => 'سنورس', 'name_en' => 'Sinnuris', 'fee' => 30],
                ['name_ar' => 'طامية', 'name_en' => 'Tamiya', 'fee' => 30],
                ['name_ar' => 'إبشواي', 'name_en' => 'Ibshaway', 'fee' => 30],
                ['name_ar' => 'يوسف الصديق', 'name_en' => 'Youssef El Seddik', 'fee' => 35],
            ],
            'بني سويف' => [
                ['name_ar' => 'بني سويف', 'name_en' => 'Beni Suef', 'fee' => 30],
                ['name_ar' => 'الواسطى', 'name_en' => 'Nasser', 'fee' => 30],
                ['name_ar' => 'ناصر', 'name_en' => 'Naser', 'fee' => 30],
                ['name_ar' => 'إهناسيا', 'name_en' => 'Ihnasia', 'fee' => 30],
                ['name_ar' => 'ببا', 'name_en' => 'Beba', 'fee' => 30],
            ],
            'المنيا' => [
                ['name_ar' => 'المنيا', 'name_en' => 'Minya', 'fee' => 30],
                ['name_ar' => 'ملوي', 'name_en' => 'Mallawi', 'fee' => 30],
                ['name_ar' => 'سمالوط', 'name_en' => 'Samalut', 'fee' => 30],
                ['name_ar' => 'بني مزار', 'name_en' => 'Beni Mazar', 'fee' => 30],
                ['name_ar' => 'مغاغة', 'name_en' => 'Maghagha', 'fee' => 30],
            ],
            'أسيوط' => [
                ['name_ar' => 'أسيوط', 'name_en' => 'Assiut', 'fee' => 30],
                ['name_ar' => 'ديروط', 'name_en' => 'Dairut', 'fee' => 30],
                ['name_ar' => 'منفلوط', 'name_en' => 'Manfalut', 'fee' => 30],
                ['name_ar' => 'القوصية', 'name_en' => 'El Qusiya', 'fee' => 30],
                ['name_ar' => 'أبو تيج', 'name_en' => 'Abu Tig', 'fee' => 30],
            ],
            'سوهاج' => [
                ['name_ar' => 'سوهاج', 'name_en' => 'Sohag', 'fee' => 30],
                ['name_ar' => 'طهطا', 'name_en' => 'Tahta', 'fee' => 30],
                ['name_ar' => 'جرجا', 'name_en' => 'Girga', 'fee' => 30],
                ['name_ar' => 'أخميم', 'name_en' => 'Akhmim', 'fee' => 30],
                ['name_ar' => 'المراغة', 'name_en' => 'El Maragha', 'fee' => 30],
            ],
            'قنا' => [
                ['name_ar' => 'قنا', 'name_en' => 'Qena', 'fee' => 30],
                ['name_ar' => 'نجع حمادي', 'name_en' => 'Nag Hammadi', 'fee' => 30],
                ['name_ar' => 'قوص', 'name_en' => 'Qus', 'fee' => 30],
                ['name_ar' => 'دشنا', 'name_en' => 'Dishna', 'fee' => 30],
                ['name_ar' => 'أبو تشت', 'name_en' => 'Abu Tesht', 'fee' => 30],
            ],
            'الأقصر' => [
                ['name_ar' => 'الأقصر', 'name_en' => 'Luxor', 'fee' => 30],
                ['name_ar' => 'إسنا', 'name_en' => 'Esna', 'fee' => 30],
                ['name_ar' => 'أرمنت', 'name_en' => 'Armant', 'fee' => 30],
                ['name_ar' => 'الطود', 'name_en' => 'El Tod', 'fee' => 30],
                ['name_ar' => 'القرنة', 'name_en' => 'Qurna', 'fee' => 30],
            ],
            'أسوان' => [
                ['name_ar' => 'أسوان', 'name_en' => 'Aswan', 'fee' => 35],
                ['name_ar' => 'كوم أمبو', 'name_en' => 'Kom Ombo', 'fee' => 35],
                ['name_ar' => 'إدفو', 'name_en' => 'Edfu', 'fee' => 35],
                ['name_ar' => 'نصر النوبة', 'name_en' => 'Nasr El Nuba', 'fee' => 40],
                ['name_ar' => 'دراو', 'name_en' => 'Daraw', 'fee' => 35],
            ],
            'البحر الأحمر' => [
                ['name_ar' => 'الغردقة', 'name_en' => 'Hurghada', 'fee' => 40],
                ['name_ar' => 'سفاجا', 'name_en' => 'Safaga', 'fee' => 40],
                ['name_ar' => 'القصير', 'name_en' => 'El Quseir', 'fee' => 45],
                ['name_ar' => 'مرسى علم', 'name_en' => 'Marsa Alam', 'fee' => 50],
                ['name_ar' => 'رأس غارب', 'name_en' => 'Ras Gharib', 'fee' => 40],
            ],
            'الوادي الجديد' => [
                ['name_ar' => 'الخارجة', 'name_en' => 'Kharga', 'fee' => 40],
                ['name_ar' => 'الداخلة', 'name_en' => 'Dakhla', 'fee' => 45],
                ['name_ar' => 'الفرافرة', 'name_en' => 'Farafra', 'fee' => 50],
                ['name_ar' => 'باريس', 'name_en' => 'Paris', 'fee' => 45],
            ],
            'مطروح' => [
                ['name_ar' => 'مرسى مطروح', 'name_en' => 'Marsa Matrouh', 'fee' => 40],
                ['name_ar' => 'العلمين', 'name_en' => 'El Alamein', 'fee' => 45],
                ['name_ar' => 'الحمام', 'name_en' => 'El Hammam', 'fee' => 40],
                ['name_ar' => 'سيدي براني', 'name_en' => 'Sidi Barrani', 'fee' => 50],
                ['name_ar' => 'السلوم', 'name_en' => 'Salloum', 'fee' => 50],
            ],
        ];
    }

    private function defaultFee(string $governorate, int $index): float
    {
        $base = match ($governorate) {
            'القاهرة', 'الجيزة', 'القليوبية' => 25,
            'الإسكندرية', 'بورسعيد', 'السويس', 'الإسماعيلية' => 30,
            default => 35,
        };

        return $base + ($index * 0);
    }
}
