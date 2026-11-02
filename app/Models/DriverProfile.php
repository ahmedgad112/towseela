<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Support\PlatformSettings;
use Database\Factories\DriverProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'vehicle_type', 'license_number', 'is_available', 'status'])]
class DriverProfile extends Model
{
    /** @use HasFactory<DriverProfileFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_available' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isOperationallyAvailable(): bool
    {
        if (! $this->is_available || $this->user?->is_active === false) {
            return false;
        }

        $activeCount = Order::query()
            ->where('driver_id', $this->user_id)
            ->whereIn('status', array_map(
                fn (OrderStatus $status): string => $status->value,
                array_filter(OrderStatus::cases(), fn (OrderStatus $status): bool => $status->isActiveAssignment()),
            ))
            ->count();

        return $activeCount < app(PlatformSettings::class)->operations()['driver_active_order_limit'];
    }
}
