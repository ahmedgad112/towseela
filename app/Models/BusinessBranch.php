<?php

namespace App\Models;

use Database\Factories\BusinessBranchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'business_id',
    'delivery_zone_id',
    'name_ar',
    'address',
    'phone',
    'lat',
    'lng',
    'is_active',
])]
class BusinessBranch extends Model
{
    /** @use HasFactory<BusinessBranchFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function deliveryZone(): BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class);
    }

    public function hours(): HasMany
    {
        return $this->hasMany(BusinessHour::class);
    }
}
