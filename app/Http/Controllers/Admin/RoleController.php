<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Support\AuditLogger;
use App\Support\PermissionCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('roles.view'), 403);

        return Inertia::render('Admin/Roles/Index', [
            'roles' => Role::query()
                ->with('permissions')
                ->withCount('users')
                ->orderByDesc('is_system')
                ->orderBy('name')
                ->get()
                ->map(fn (Role $role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'label' => $role->label ?: (PermissionCatalog::systemRoles()[$role->name] ?? $role->name),
                    'is_system' => (bool) $role->is_system,
                    'users_count' => $role->users_count,
                    'permissions' => $role->permissions->pluck('name'),
                ]),
            'permissions' => Permission::query()
                ->orderBy('group')
                ->orderBy('name')
                ->get()
                ->map(fn (Permission $permission) => [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'label' => $permission->label ?: $permission->name,
                    'group' => $permission->group ?: 'أخرى',
                    'is_system' => (bool) $permission->is_system,
                ]),
            'can' => [
                'createRole' => $request->user()->can('roles.create'),
                'updateRole' => $request->user()->can('roles.update'),
                'deleteRole' => $request->user()->can('roles.delete'),
                'createPermission' => $request->user()->can('permissions.create'),
                'deletePermission' => $request->user()->can('permissions.delete'),
            ],
        ]);
    }

    public function store(Request $request, AuditLogger $audit): RedirectResponse
    {
        abort_unless($request->user()->can('roles.create'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:50', 'regex:/^[a-z][a-z0-9_]*$/', 'unique:roles,name'],
            'label' => ['required', 'string', 'max:80'],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role = Role::query()->create([
            'name' => $data['name'],
            'guard_name' => 'web',
            'label' => $data['label'],
            'is_system' => false,
        ]);

        $role->syncPermissions($data['permissions'] ?? []);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $audit->record(AuditAction::RoleCreated, $role, $request->user(), [
            'name' => $role->name,
            'permissions' => $role->permissions()->pluck('name')->all(),
        ]);

        return back()->with('success', __('تم إنشاء الدور.'));
    }

    public function update(Request $request, Role $role, AuditLogger $audit): RedirectResponse
    {
        abort_unless($request->user()->can('roles.update'), 403);

        if ($role->name === 'super_admin') {
            return back()->with('error', __('لا يمكن تعديل دور مدير المنصة.'));
        }

        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:80'],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        if (! $role->is_system && isset($data['label'])) {
            $role->update(['label' => $data['label']]);
        }

        $role->syncPermissions($data['permissions'] ?? []);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $audit->record(AuditAction::RoleUpdated, $role, $request->user(), [
            'name' => $role->name,
            'permissions' => $role->permissions()->pluck('name')->all(),
        ]);

        return back()->with('success', __('تم تحديث صلاحيات الدور.'));
    }

    public function destroy(Request $request, Role $role, AuditLogger $audit): RedirectResponse
    {
        abort_unless($request->user()->can('roles.delete'), 403);

        if ($role->name === 'super_admin' || $role->is_system) {
            return back()->with('error', __('لا يمكن حذف الأدوار الأساسية للنظام.'));
        }

        if ($role->users()->exists()) {
            return back()->with('error', __('انقل الموظفين من هذا الدور قبل حذفه.'));
        }

        $audit->record(AuditAction::RoleDeleted, $role, $request->user(), [
            'name' => $role->name,
        ]);

        $role->delete();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return back()->with('success', __('تم حذف الدور.'));
    }
}
