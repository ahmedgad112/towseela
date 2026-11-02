<?php

namespace App\Support;

use App\Models\PlatformSetting;

class PlatformSettings
{
    /**
     * @return array{
     *     brand: array{name_ar: string, name_en: string, tagline: string, support_phone: string, support_email: string, whatsapp: string},
     *     operations: array{currency: string, currency_label: string, allow_mixed_business_cart: bool, require_proof_of_delivery_note: bool, driver_active_order_limit: int, overdue_minutes: int},
     *     map: array{default_lat: float, default_lng: float, default_city: string},
     *     features: array{customer_registration_enabled: bool, maintenance_mode: bool, maintenance_message: string}
     * }
     */
    public function all(): array
    {
        return [
            'brand' => $this->brand(),
            'operations' => $this->operations(),
            'map' => $this->map(),
            'features' => $this->features(),
        ];
    }

    /**
     * @return array{name_ar: string, name_en: string, tagline: string, support_phone: string, support_email: string, whatsapp: string}
     */
    public function brand(): array
    {
        $stored = PlatformSetting::getValue('brand', []);

        return [
            'name_ar' => (string) ($stored['name_ar'] ?? config('app.name', 'توسيلا')),
            'name_en' => (string) ($stored['name_en'] ?? 'Towseela'),
            'tagline' => (string) ($stored['tagline'] ?? 'توصيل احترافي'),
            'support_phone' => (string) ($stored['support_phone'] ?? '19900'),
            'support_email' => (string) ($stored['support_email'] ?? ''),
            'whatsapp' => (string) ($stored['whatsapp'] ?? ''),
        ];
    }

    /**
     * @return array{currency: string, currency_label: string, allow_mixed_business_cart: bool, require_proof_of_delivery_note: bool, driver_active_order_limit: int, overdue_minutes: int}
     */
    public function operations(): array
    {
        $stored = PlatformSetting::getValue('operations', []);

        return [
            'currency' => (string) ($stored['currency'] ?? config('towseela.currency', 'EGP')),
            'currency_label' => (string) ($stored['currency_label'] ?? config('towseela.currency_label', 'ج.م')),
            'allow_mixed_business_cart' => (bool) ($stored['allow_mixed_business_cart'] ?? config('towseela.allow_mixed_business_cart', false)),
            'require_proof_of_delivery_note' => (bool) ($stored['require_proof_of_delivery_note'] ?? config('towseela.require_proof_of_delivery_note', false)),
            'driver_active_order_limit' => (int) ($stored['driver_active_order_limit'] ?? config('towseela.driver_active_order_limit', 3)),
            'overdue_minutes' => (int) ($stored['overdue_minutes'] ?? config('towseela.overdue_minutes', 90)),
        ];
    }

    /**
     * @return array{default_lat: float, default_lng: float, default_city: string}
     */
    public function map(): array
    {
        $stored = PlatformSetting::getValue('map', []);

        return [
            'default_lat' => (float) ($stored['default_lat'] ?? config('towseela.map.default_lat', 30.0444)),
            'default_lng' => (float) ($stored['default_lng'] ?? config('towseela.map.default_lng', 31.2357)),
            'default_city' => (string) ($stored['default_city'] ?? config('towseela.map.default_city', 'القاهرة')),
        ];
    }

    /**
     * @return array{customer_registration_enabled: bool, maintenance_mode: bool, maintenance_message: string}
     */
    public function features(): array
    {
        $stored = PlatformSetting::getValue('features', []);

        return [
            'customer_registration_enabled' => (bool) ($stored['customer_registration_enabled'] ?? true),
            'maintenance_mode' => (bool) ($stored['maintenance_mode'] ?? false),
            'maintenance_message' => (string) ($stored['maintenance_message'] ?? 'المنصة تحت الصيانة مؤقتاً. نعود قريباً.'),
        ];
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->all(), $key, $default);
    }

    /**
     * @param  array<string, mixed>  $brand
     * @param  array<string, mixed>  $operations
     * @param  array<string, mixed>  $map
     * @param  array<string, mixed>  $features
     */
    public function update(array $brand, array $operations, array $map, array $features): void
    {
        PlatformSetting::setValue('brand', [
            ...$this->brand(),
            ...$brand,
        ]);

        PlatformSetting::setValue('operations', [
            ...$this->operations(),
            ...$operations,
        ]);

        PlatformSetting::setValue('map', [
            ...$this->map(),
            ...$map,
        ]);

        PlatformSetting::setValue('features', [
            ...$this->features(),
            ...$features,
        ]);
    }
}
