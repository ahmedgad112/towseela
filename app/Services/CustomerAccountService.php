<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CustomerAccountService
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @param  array{name: string, phone: string, email?: string|null, password?: string|null}  $data
     */
    public function create(array $data, ?User $actor = null): User
    {
        $phone = trim($data['phone']);
        $email = filled($data['email'] ?? null)
            ? trim((string) $data['email'])
            : $this->emailFromPhone($phone);

        if (User::query()->where('phone', $phone)->exists()) {
            throw ValidationException::withMessages([
                'phone' => __('رقم الهاتف مستخدم مسبقاً.'),
            ]);
        }

        if (User::query()->where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => __('البريد الإلكتروني مستخدم مسبقاً.'),
            ]);
        }

        $user = User::query()->create([
            'name' => trim($data['name']),
            'phone' => $phone,
            'email' => $email,
            'password' => filled($data['password'] ?? null) ? $data['password'] : Str::password(16),
            'is_active' => true,
        ]);

        $user->assignRole('customer');

        $this->audit->record(AuditAction::UserCreated, $user, $actor, [
            'name' => $user->name,
            'role' => 'customer',
            'source' => 'staff',
        ]);

        return $user->fresh();
    }

    /**
     * @return Collection<int, User>
     */
    public function search(string $term, int $limit = 15)
    {
        $term = trim($term);

        if ($term === '') {
            return User::query()->whereRaw('0 = 1')->get();
        }

        return User::role('customer')
            ->where(function ($query) use ($term) {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            })
            ->withCount('orders')
            ->with(['addresses.deliveryZone'])
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    private function emailFromPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?: Str::lower(Str::random(10));

        return "{$digits}@phone.towseela.local";
    }
}
