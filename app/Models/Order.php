<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Support\PlatformSettings;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'number',
    'user_id',
    'business_id',
    'business_branch_id',
    'driver_id',
    'delivery_zone_id',
    'coupon_id',
    'status',
    'payment_method',
    'payment_status',
    'address_snapshot',
    'subtotal',
    'discount',
    'delivery_fee',
    'grand_total',
    'customer_notes',
    'internal_notes',
    'cancel_reason',
    'failure_reason',
    'proof_of_delivery',
    'checkout_token',
    'confirmed_at',
    'prepared_at',
    'assigned_at',
    'picked_up_at',
    'out_for_delivery_at',
    'delivered_at',
    'cancelled_at',
    'failed_at',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'payment_method' => PaymentMethod::class,
            'payment_status' => PaymentStatus::class,
            'address_snapshot' => 'array',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'confirmed_at' => 'datetime',
            'prepared_at' => 'datetime',
            'assigned_at' => 'datetime',
            'picked_up_at' => 'datetime',
            'out_for_delivery_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(BusinessBranch::class, 'business_branch_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function deliveryZone(): BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(DriverAssignment::class);
    }

    public function activeAssignment(): HasOne
    {
        return $this->hasOne(DriverAssignment::class)->where('status', 'active');
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function isOverdue(): bool
    {
        if ($this->status->isTerminal()) {
            return false;
        }

        $overdueMinutes = (int) app(PlatformSettings::class)->operations()['overdue_minutes'];

        return $this->created_at?->lte(now()->subMinutes($overdueMinutes)) ?? false;
    }
}
