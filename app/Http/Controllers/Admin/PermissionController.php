<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionController extends Controller
{
    public function store(Request $request, AuditLogger $audit): RedirectResponse
    {
        abort_unless($request->user()->can('permissions.create'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', 'regex:/^[a-z][a-z0-9_.]*$/', 'unique:permissions,name'],
            'label' => ['required', 'string', 'max:80'],
            'group' => ['required', 'string', 'max:80'],
        ]);

        $permission = Permission::query()->create([
            'name' => $data['name'],
            'guard_name' => 'web',
            'label' => $data['label'],
            'group' => $data['group'],
            'is_system' => false,
        ]);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $audit->record(AuditAction::PermissionCreated, $permission, $request->user(), [
            'name' => $permission->name,
        ]);

        return back()->with('success', __('تم إنشاء الصلاحية. اربطها بدور ثم استخدم اسمها لإظهار أو إخفاء زر.'));
    }

    public function destroy(Request $request, Permission $permission, AuditLogger $audit): RedirectResponse
    {
        abort_unless($request->user()->can('permissions.delete'), 403);

        if ($permission->is_system) {
            return back()->with('error', __('لا يمكن حذف صلاحيات النظام الأساسية.'));
        }

        $audit->record(AuditAction::PermissionDeleted, $permission, $request->user(), [
            'name' => $permission->name,
        ]);

        $permission->delete();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return back()->with('success', __('تم حذف الصلاحية.'));
    }
}
