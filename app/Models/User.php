<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'google_id', 'phone', 'password', 'is_active', 'blocked_until', 'block_reason', 'email_verified_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'blocked_until' => 'datetime',
        ];
    }

    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function isPermanentlyBlocked(): bool
    {
        return ! $this->is_active && $this->blocked_until === null;
    }

    public function isTemporarilyBlocked(): bool
    {
        return ! $this->is_active
            && $this->blocked_until !== null
            && $this->blocked_until->isFuture();
    }

    public function clearExpiredBlock(): bool
    {
        if ($this->is_active || $this->blocked_until === null || $this->blocked_until->isFuture()) {
            return false;
        }

        $this->forceFill([
            'is_active' => true,
            'blocked_until' => null,
            'block_reason' => null,
        ])->save();

        return true;
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function defaultAddress(): HasOne
    {
        return $this->hasOne(CustomerAddress::class)->where('is_default', true);
    }

    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function driverProfile(): HasOne
    {
        return $this->hasOne(DriverProfile::class);
    }

    public function driverAssignments(): HasMany
    {
        return $this->hasMany(DriverAssignment::class, 'driver_id');
    }

    public function assignedOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'driver_id');
    }

    public function managedBusinesses(): HasMany
    {
        return $this->hasMany(Business::class, 'manager_user_id');
    }

    public function isStaff(): bool
    {
        $roles = $this->relationLoaded('roles')
            ? $this->roles
            : $this->roles()->get();

        return $roles->contains(
            fn ($role): bool => ! in_array($role->name, ['customer', 'driver'], true),
        );
    }

    public function portalHome(): string
    {
        if ($this->hasAnyRole(['super_admin', 'admin', 'business_manager'])) {
            return route('admin.dashboard');
        }

        if ($this->hasAnyRole(['call_center_agent', 'dispatcher'])) {
            return route('call-center.dashboard');
        }

        if ($this->hasRole('driver')) {
            return route('driver.dashboard');
        }

        return route('customer.home');
    }

    public function profilePath(): string
    {
        if ($this->hasRole('customer') && ! $this->isStaff() && ! $this->hasRole('driver')) {
            return route('customer.profile');
        }

        return route('profile.edit');
    }
}
