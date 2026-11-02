<?php

namespace App\Models;

use Database\Factories\PlatformSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

#[Fillable(['key', 'value'])]
class PlatformSetting extends Model
{
    /** @use HasFactory<PlatformSettingFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }

    public static function getValue(string $key, mixed $default = null): mixed
    {
        /** @var array<string, mixed> $settings */
        $settings = Cache::remember('platform_settings', 60, function () {
            return static::query()
                ->get(['key', 'value'])
                ->mapWithKeys(fn (self $setting): array => [$setting->key => $setting->value])
                ->all();
        });

        if (! is_array($settings)) {
            Cache::forget('platform_settings');

            return $default;
        }

        return $settings[$key] ?? $default;
    }

    public static function setValue(string $key, mixed $value): self
    {
        $setting = static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value],
        );

        Cache::forget('platform_settings');

        return $setting;
    }
}
