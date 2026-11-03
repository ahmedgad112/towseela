<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomerModerationService
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @param  array{duration: string, blocked_until?: string|null, reason?: string|null}  $data
     */
    public function block(User $customer, array $data, User $actor): User
    {
        $this->ensureCustomer($customer);

        $blockedUntil = $this->resolveBlockedUntil($data['duration'], $data['blocked_until'] ?? null);
        $reason = filled($data['reason'] ?? null) ? trim((string) $data['reason']) : null;

        $customer->forceFill([
            'is_active' => false,
            'blocked_until' => $blockedUntil,
            'block_reason' => $reason,
        ])->save();

        $this->invalidateSessions($customer);

        $this->audit->record(AuditAction::UserUpdated, $customer, $actor, [
            'action' => 'customer.blocked',
            'name' => $customer->name,
            'blocked_until' => $blockedUntil?->toIso8601String(),
            'permanent' => $blockedUntil === null,
            'reason' => $reason,
        ]);

        return $customer->fresh();
    }

    public function unblock(User $customer, User $actor): User
    {
        $this->ensureCustomer($customer);

        $customer->forceFill([
            'is_active' => true,
            'blocked_until' => null,
            'block_reason' => null,
        ])->save();

        $this->audit->record(AuditAction::UserUpdated, $customer, $actor, [
            'action' => 'customer.unblocked',
            'name' => $customer->name,
        ]);

        return $customer->fresh();
    }

    public function delete(User $customer, User $actor): void
    {
        $this->ensureCustomer($customer);

        if ($customer->orders()->exists()) {
            throw ValidationException::withMessages([
                'customer' => __('لا يمكن حذف العميل لوجود طلبات مرتبطة. يمكنك حظر الحساب بدلاً من ذلك.'),
            ]);
        }

        $this->audit->record(AuditAction::UserDeleted, $customer, $actor, [
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'role' => 'customer',
        ]);

        $this->invalidateSessions($customer);

        $customer->syncRoles([]);
        $customer->delete();
    }

    private function ensureCustomer(User $customer): void
    {
        abort_unless($customer->hasRole('customer'), 404);
    }

    private function resolveBlockedUntil(string $duration, ?string $customUntil): mixed
    {
        return match ($duration) {
            'permanent' => null,
            '1_day' => now()->addDay(),
            '7_days' => now()->addDays(7),
            '30_days' => now()->addDays(30),
            'custom' => filled($customUntil) ? now()->parse($customUntil) : throw ValidationException::withMessages([
                'blocked_until' => __('حدد تاريخ انتهاء الحظر.'),
            ]),
            default => throw ValidationException::withMessages([
                'duration' => __('مدة الحظر غير صالحة.'),
            ]),
        };
    }

    private function invalidateSessions(User $customer): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        DB::table(config('session.table', 'sessions'))
            ->where('user_id', $customer->id)
            ->delete();
    }
}
