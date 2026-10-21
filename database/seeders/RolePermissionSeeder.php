<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (PermissionCatalog::definitions() as $definition) {
            $permission = Permission::findOrCreate($definition['name'], 'web');
            $permission->forceFill([
                'label' => $definition['label'],
                'group' => $definition['group'],
                'is_system' => true,
            ])->save();
        }

        foreach (PermissionCatalog::defaultRolePermissions() as $roleName => $rolePermissions) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->forceFill([
                'label' => PermissionCatalog::systemRoles()[$roleName] ?? $roleName,
                'is_system' => true,
            ])->save();
            $role->syncPermissions($rolePermissions);
        }

        $accounts = [
            ['name' => 'مدير المنصة', 'email' => 'superadmin@towseela.test', 'phone' => '01000000001', 'role' => 'super_admin'],
            ['name' => 'مدير تشغيلي', 'email' => 'admin@towseela.test', 'phone' => '01000000002', 'role' => 'admin'],
            ['name' => 'وكيل الاتصال', 'email' => 'agent@towseela.test', 'phone' => '01000000003', 'role' => 'call_center_agent'],
            ['name' => 'منسق التوزيع', 'email' => 'dispatcher@towseela.test', 'phone' => '01000000004', 'role' => 'dispatcher'],
            ['name' => 'مندوب أحمد', 'email' => 'driver@towseela.test', 'phone' => '01000000005', 'role' => 'driver'],
            ['name' => 'سارة العميل', 'email' => 'customer@towseela.test', 'phone' => '01000000006', 'role' => 'customer'],
            ['name' => 'مدير المطعم', 'email' => 'manager@towseela.test', 'phone' => '01000000007', 'role' => 'business_manager'],
        ];

        foreach ($accounts as $account) {
            $user = User::query()->updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'phone' => $account['phone'],
                    'password' => Hash::make('password'),
                    'is_active' => true,
                ],
            );

            $user->syncRoles([$account['role']]);
        }
    }
}
