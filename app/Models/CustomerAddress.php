<?php

namespace App\Models;

use App\Enums\AddressLabel;
use Database\Factories\CustomerAddressFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'label',
    'line1',
    'line2',
    'city',
    'delivery_zone_id',
    'notes',
    'lat',
    'lng',
    'is_default',
])]
class CustomerAddress extends Model
{
    /** @use HasFactory<CustomerAddressFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'label' => AddressLabel::class,
            'is_default' => 'boolean',
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function deliveryZone(): BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function toSnapshot(): array
    {
        return [
            'label' => $this->label->value,
            'label_text' => $this->label->label(),
            'line1' => $this->line1,
            'line2' => $this->line2,
            'city' => $this->city,
            'notes' => $this->notes,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'zone' => $this->deliveryZone?->name_ar,
            'delivery_zone_id' => $this->delivery_zone_id,
        ];
    }
}
