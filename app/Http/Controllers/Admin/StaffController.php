<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class StaffController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $actor = $request->user();

        return Inertia::render('Admin/Staff/Index', [
            'staff' => User::query()
                ->whereHas('roles', fn ($query) => $query->whereNotIn('name', ['customer', 'driver']))
                ->with('roles')
                ->orderBy('name')
                ->orderBy('id')
                ->paginate(15)
                ->through(fn (User $user) => $this->staffPayload($actor, $user)),
            'roles' => Role::query()
                ->where('guard_name', 'web')
                ->whereNotIn('name', ['super_admin', 'customer', 'driver'])
                ->orderBy('name')
                ->get(['id', 'name', 'label']),
            'can' => [
                'create' => $actor->can('create', User::class),
                'update' => $actor->can('users.update'),
                'delete' => $actor->can('users.delete'),
            ],
        ]);
    }

    public function store(Request $request, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('create', User::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone'],
            'password' => ['required', Password::defaults()],
            'role' => $this->assignableRoleRules(),
        ]);

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => $data['password'],
            'is_active' => true,
        ]);

        $user->assignRole($data['role']);

        $audit->record(AuditAction::UserCreated, $user, $request->user(), [
            'name' => $user->name,
            'role' => $data['role'],
        ]);

        return back()->with('success', __('تم إنشاء حساب الموظف.'));
    }

    public function update(Request $request, User $staff, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('update', $staff);
        $this->ensureStaffMember($staff);

        $actor = $request->user();
        $roleIsLocked = $actor->is($staff) || $staff->hasRole('super_admin');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($staff->id)],
            'phone' => ['required', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($staff->id)],
            'password' => ['nullable', Password::defaults()],
            'role' => $roleIsLocked ? ['nullable', 'string'] : $this->assignableRoleRules(),
            'is_active' => ['required', 'boolean'],
        ]);

        if ($actor->is($staff) && ! $request->boolean('is_active')) {
            return back()->with('error', __('لا يمكنك تعطيل حسابك.'));
        }

        if ($staff->hasRole('super_admin') && ! $request->boolean('is_active')) {
            return back()->with('error', __('لا يمكن تعطيل مدير المنصة.'));
        }

        $staff->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'is_active' => $request->boolean('is_active'),
            ...filled($data['password'] ?? null) ? ['password' => $data['password']] : [],
        ]);

        if (! $roleIsLocked) {
            $staff->syncRoles([$data['role']]);
        }

        $audit->record(AuditAction::UserUpdated, $staff, $actor, [
            'name' => $staff->name,
            'role' => $staff->roles()->pluck('name')->all(),
            'is_active' => $staff->is_active,
        ]);

        return back()->with('success', __('تم تحديث حساب الموظف.'));
    }

    public function destroy(Request $request, User $staff, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('delete', $staff);
        $this->ensureStaffMember($staff);

        if ($request->user()->is($staff)) {
            return back()->with('error', __('لا يمكنك حذف حسابك.'));
        }

        if ($staff->hasRole('super_admin')) {
            return back()->with('error', __('لا يمكن حذف مدير المنصة.'));
        }

        if ($staff->orders()->exists() || $staff->driverAssignments()->exists()) {
            return back()->with('error', __('لا يمكن حذف الحساب لوجود سجلات مرتبطة. يمكنك تعطيل الحساب بدلاً من ذلك.'));
        }

        $audit->record(AuditAction::UserDeleted, $staff, $request->user(), [
            'name' => $staff->name,
            'email' => $staff->email,
            'roles' => $staff->getRoleNames()->all(),
        ]);

        $staff->syncRoles([]);
        $staff->delete();

        return back()->with('success', __('تم حذف حساب الموظف.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function staffPayload(User $actor, User $user): array
    {
        $isSuperAdmin = $user->roles->contains('name', 'super_admin');

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'is_active' => $user->is_active,
            'is_self' => $actor->is($user),
            'is_super_admin' => $isSuperAdmin,
            'role' => $user->roles->first()?->name,
            'roles' => $user->roles->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'label' => $role->label,
            ]),
            'can_update' => $actor->can('update', $user),
            'can_delete' => ! $actor->is($user) && ! $isSuperAdmin && $actor->can('delete', $user),
        ];
    }

    private function ensureStaffMember(User $staff): void
    {
        abort_unless($staff->isStaff(), 404);
    }

    /**
     * @return list<mixed>
     */
    private function assignableRoleRules(): array
    {
        return [
            'required',
            'string',
            Rule::exists('roles', 'name')->where('guard_name', 'web'),
            Rule::notIn(['super_admin', 'customer', 'driver']),
        ];
    }
}
