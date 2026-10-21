<?php

namespace App\Support;

class PermissionCatalog
{
    /**
     * @return array<int, array{name: string, label: string, group: string}>
     */
    public static function definitions(): array
    {
        return [
            ['name' => 'dashboard.view', 'label' => 'عرض لوحة التحكم', 'group' => 'لوحة التحكم'],
            ['name' => 'users.view', 'label' => 'عرض المستخدمين', 'group' => 'المستخدمون'],
            ['name' => 'users.create', 'label' => 'إنشاء مستخدمين', 'group' => 'المستخدمون'],
            ['name' => 'users.update', 'label' => 'تعديل المستخدمين', 'group' => 'المستخدمون'],
            ['name' => 'users.delete', 'label' => 'حذف المستخدمين', 'group' => 'المستخدمون'],
            ['name' => 'businesses.view', 'label' => 'عرض المتاجر', 'group' => 'المتاجر'],
            ['name' => 'businesses.manage', 'label' => 'إضافة وتعديل المتاجر', 'group' => 'المتاجر'],
            ['name' => 'categories.manage', 'label' => 'إضافة وتعديل التصنيفات', 'group' => 'التصنيفات'],
            ['name' => 'products.manage', 'label' => 'إضافة وتعديل المنتجات', 'group' => 'المنتجات'],
            ['name' => 'offers.manage', 'label' => 'إضافة وتعديل العروض', 'group' => 'العروض'],
            ['name' => 'delivery_zones.manage', 'label' => 'إضافة وتعديل مناطق التوصيل', 'group' => 'المناطق'],
            ['name' => 'orders.view', 'label' => 'عرض الطلبات', 'group' => 'الطلبات'],
            ['name' => 'orders.create', 'label' => 'إنشاء طلبات', 'group' => 'الطلبات'],
            ['name' => 'orders.update', 'label' => 'تغيير حالة الطلب', 'group' => 'الطلبات'],
            ['name' => 'orders.confirm', 'label' => 'تأكيد الطلب', 'group' => 'الطلبات'],
            ['name' => 'orders.assign_driver', 'label' => 'تعيين مندوب', 'group' => 'الطلبات'],
            ['name' => 'orders.cancel', 'label' => 'رفض أو إلغاء الطلب', 'group' => 'الطلبات'],
            ['name' => 'orders.note', 'label' => 'حفظ ملاحظة داخلية', 'group' => 'الطلبات'],
            ['name' => 'orders.mark_delivered', 'label' => 'تأكيد أو فشل التسليم', 'group' => 'الطلبات'],
            ['name' => 'drivers.view', 'label' => 'عرض المناديب', 'group' => 'المناديب'],
            ['name' => 'drivers.manage', 'label' => 'إضافة وتعديل المناديب', 'group' => 'المناديب'],
            ['name' => 'reports.view', 'label' => 'عرض التقارير', 'group' => 'التقارير'],
            ['name' => 'reports.export', 'label' => 'تصدير التقارير', 'group' => 'التقارير'],
            ['name' => 'settings.manage', 'label' => 'حفظ إعدادات المنصة', 'group' => 'الإعدادات'],
            ['name' => 'roles.view', 'label' => 'عرض الأدوار', 'group' => 'الأدوار'],
            ['name' => 'roles.create', 'label' => 'إنشاء دور', 'group' => 'الأدوار'],
            ['name' => 'roles.update', 'label' => 'تعديل صلاحيات الدور', 'group' => 'الأدوار'],
            ['name' => 'roles.delete', 'label' => 'حذف دور', 'group' => 'الأدوار'],
            ['name' => 'permissions.create', 'label' => 'إنشاء صلاحية', 'group' => 'الصلاحيات'],
            ['name' => 'permissions.delete', 'label' => 'حذف صلاحية', 'group' => 'الصلاحيات'],
            ['name' => 'audit.view', 'label' => 'عرض سجل التدقيق', 'group' => 'التدقيق'],
        ];
    }

    /**
     * @return list<string>
     */
    public static function names(): array
    {
        return array_column(self::definitions(), 'name');
    }

    /**
     * @return array<string, string>
     */
    public static function systemRoles(): array
    {
        return [
            'super_admin' => 'مدير المنصة',
            'admin' => 'مدير تشغيلي',
            'call_center_agent' => 'مركز الاتصال',
            'dispatcher' => 'منسق التوزيع',
            'driver' => 'مندوب',
            'customer' => 'عميل',
            'business_manager' => 'مدير المتجر',
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    public static function defaultRolePermissions(): array
    {
        return [
            'super_admin' => self::names(),
            'admin' => [
                'dashboard.view',
                'users.view',
                'users.create',
                'users.update',
                'businesses.view',
                'businesses.manage',
                'categories.manage',
                'products.manage',
                'offers.manage',
                'delivery_zones.manage',
                'orders.view',
                'orders.create',
                'orders.update',
                'orders.confirm',
                'orders.assign_driver',
                'orders.cancel',
                'orders.note',
                'drivers.view',
                'drivers.manage',
                'reports.view',
                'reports.export',
                'audit.view',
            ],
            'call_center_agent' => [
                'dashboard.view',
                'orders.view',
                'orders.confirm',
                'orders.cancel',
                'orders.note',
                'users.view',
            ],
            'dispatcher' => [
                'dashboard.view',
                'orders.view',
                'orders.assign_driver',
                'orders.update',
                'drivers.view',
            ],
            'driver' => [
                'orders.view',
                'orders.mark_delivered',
            ],
            'customer' => [
                'orders.create',
            ],
            'business_manager' => [
                'dashboard.view',
                'businesses.view',
                'products.manage',
                'orders.view',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function assignableStaffRoles(): array
    {
        return array_values(array_filter(
            array_keys(self::systemRoles()),
            fn (string $role): bool => ! in_array($role, ['super_admin', 'customer', 'driver'], true),
        ));
    }

    public static function isSystemRole(string $name): bool
    {
        return array_key_exists($name, self::systemRoles());
    }

    public static function isSystemPermission(string $name): bool
    {
        return in_array($name, self::names(), true);
    }
}
