<?php

namespace App\Models;

use App\Support\CatalogImage;
use Database\Factories\BusinessFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

#[Appends('cover_url')]
#[Fillable([
    'business_category_id',
    'manager_user_id',
    'name_ar',
    'name_en',
    'slug',
    'description',
    'logo_path',
    'cover_path',
    'phone',
    'is_active',
    'accepts_orders',
    'min_order_amount',
    'delivery_time_min',
    'delivery_time_max',
])]
class Business extends Model
{
    /** @use HasFactory<BusinessFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'accepts_orders' => 'boolean',
            'min_order_amount' => 'decimal:2',
            'delivery_time_min' => 'integer',
            'delivery_time_max' => 'integer',
        ];
    }

    /**
     * @param  Builder<Business>  $query
     * @return Builder<Business>
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function coverUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => CatalogImage::url($this->cover_path));
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BusinessCategory::class, 'business_category_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_user_id');
    }

    public function branches(): HasMany
    {
        return $this->hasMany(BusinessBranch::class);
    }

    public function hours(): HasMany
    {
        return $this->hasMany(BusinessHour::class);
    }

    public function productCategories(): HasMany
    {
        return $this->hasMany(ProductCategory::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function isOpenAt(?Carbon $moment = null): bool
    {
        $moment ??= now();
        $hour = $this->hours
            ->firstWhere('day_of_week', (int) $moment->dayOfWeek);

        if (! $hour || $hour->is_closed || ! $hour->opens_at || ! $hour->closes_at) {
            return $this->accepts_orders;
        }

        $current = $moment->format('H:i:s');

        return $current >= $hour->opens_at && $current <= $hour->closes_at;
    }
}
