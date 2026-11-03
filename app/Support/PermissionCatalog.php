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
            ['name' => 'users.view', 'label' => 'عرض الموظفين', 'group' => 'الموظفون'],
            ['name' => 'users.create', 'label' => 'إنشاء موظفين', 'group' => 'الموظفون'],
            ['name' => 'users.update', 'label' => 'تعديل الموظفين', 'group' => 'الموظفون'],
            ['name' => 'users.delete', 'label' => 'حذف الموظفين', 'group' => 'الموظفون'],
            ['name' => 'customers.view', 'label' => 'عرض العملاء', 'group' => 'العملاء'],
            ['name' => 'customers.create', 'label' => 'إنشاء عملاء', 'group' => 'العملاء'],
            ['name' => 'customers.update', 'label' => 'حظر أو فك حظر العملاء', 'group' => 'العملاء'],
            ['name' => 'customers.delete', 'label' => 'حذف العملاء', 'group' => 'العملاء'],
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
            ['name' => 'orders.delete', 'label' => 'حذف الطلب', 'group' => 'الطلبات'],
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
            ['name' => 'customer.search', 'label' => 'زر البحث', 'group' => 'أزرار العميل'],
            ['name' => 'customer.cart', 'label' => 'السلة وإضافة منتجات', 'group' => 'أزرار العميل'],
            ['name' => 'customer.checkout', 'label' => 'إتمام الطلب', 'group' => 'أزرار العميل'],
            ['name' => 'customer.coupons', 'label' => 'تطبيق كوبون', 'group' => 'أزرار العميل'],
            ['name' => 'customer.addresses', 'label' => 'إدارة العناوين', 'group' => 'أزرار العميل'],
            ['name' => 'customer.orders', 'label' => 'عرض طلباتي', 'group' => 'أزرار العميل'],
            ['name' => 'customer.reorder', 'label' => 'إعادة الطلب', 'group' => 'أزرار العميل'],
            ['name' => 'customer.cancel_order', 'label' => 'إلغاء الطلب', 'group' => 'أزرار العميل'],
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
                'users.delete',
                'customers.view',
                'customers.create',
                'customers.update',
                'customers.delete',
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
                'orders.delete',
                'orders.note',
                'drivers.view',
                'drivers.manage',
                'reports.view',
                'reports.export',
                'audit.view',
            ],
            'call_center_agent' => [
                'dashboard.view',
                'customers.view',
                'customers.create',
                'orders.view',
                'orders.create',
                'orders.confirm',
                'orders.cancel',
                'orders.note',
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
                'customer.search',
                'customer.cart',
                'customer.checkout',
                'customer.coupons',
                'customer.addresses',
                'customer.orders',
                'customer.reorder',
                'customer.cancel_order',
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
