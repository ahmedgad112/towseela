export const roleLabels = {
    super_admin: 'مدير المنصة',
    admin: 'مدير تشغيلي',
    call_center_agent: 'مركز الاتصال',
    dispatcher: 'منسق التوزيع',
    driver: 'مندوب',
    business_manager: 'مدير المتجر',
    customer: 'عميل',
};

export const permissionLabels = {
    'dashboard.view': 'عرض لوحة التحكم',
    'users.view': 'عرض المستخدمين',
    'users.create': 'إنشاء مستخدمين',
    'users.update': 'تعديل المستخدمين',
    'users.delete': 'حذف المستخدمين',
    'businesses.view': 'عرض المتاجر',
    'businesses.manage': 'إدارة المتاجر',
    'categories.manage': 'إدارة التصنيفات',
    'products.manage': 'إدارة المنتجات',
    'offers.manage': 'إدارة العروض',
    'delivery_zones.manage': 'إدارة مناطق التوصيل',
    'orders.view': 'عرض الطلبات',
    'orders.create': 'إنشاء طلبات',
    'orders.update': 'تحديث الطلبات',
    'orders.confirm': 'تأكيد الطلبات',
    'orders.assign_driver': 'تعيين مندوب',
    'orders.cancel': 'رفض أو إلغاء الطلب',
    'orders.note': 'حفظ ملاحظة داخلية',
    'orders.mark_delivered': 'تأكيد أو فشل التسليم',
    'drivers.view': 'عرض المناديب',
    'drivers.manage': 'إدارة المناديب',
    'reports.view': 'عرض التقارير',
    'reports.export': 'تصدير التقارير',
    'settings.manage': 'حفظ إعدادات المنصة',
    'roles.view': 'عرض الأدوار',
    'roles.create': 'إنشاء دور',
    'roles.update': 'تعديل صلاحيات الدور',
    'roles.delete': 'حذف دور',
    'permissions.create': 'إنشاء صلاحية',
    'permissions.delete': 'حذف صلاحية',
    'audit.view': 'عرض سجل التدقيق',
};

const workspaces = {
    super_admin: {
        title: 'لوحة المنصة',
        subtitle: 'تحكم كامل بالتشغيل والصلاحيات',
        eyebrow: 'Super Admin',
    },
    admin: {
        title: 'لوحة التشغيل',
        subtitle: 'إدارة الطلبات والمتاجر والمناديب',
        eyebrow: 'Admin',
    },
    business_manager: {
        title: 'بوابة المتجر',
        subtitle: 'منتجاتك وطلباتك في مكان واحد',
        eyebrow: 'Business Manager',
    },
    call_center_agent: {
        title: 'مركز الاتصال',
        subtitle: 'تأكيد الطلبات ودعم العملاء',
        eyebrow: 'Call Center',
    },
    dispatcher: {
        title: 'غرفة التوزيع',
        subtitle: 'تعيين المناديب ومتابعة التوصيل',
        eyebrow: 'Dispatcher',
    },
    driver: {
        title: 'بوابة المندوب',
        subtitle: 'طلباتك ومسار التسليم',
        eyebrow: 'Driver',
    },
};

export const primaryRole = (roles = []) => (
    ['super_admin', 'admin', 'business_manager', 'dispatcher', 'call_center_agent', 'driver', 'customer']
        .find((role) => roles.includes(role)) || roles[0] || null
);

export const workspaceFor = (roles = []) => workspaces[primaryRole(roles)] || {
    title: 'توسيلا',
    subtitle: 'منصة تشغيل التوصيل',
    eyebrow: 'Towseela',
};

export const roleLabel = (role, fallback = '') => roleLabels[role] || fallback || role;
export const permissionLabel = (permission, fallback = '') => permissionLabels[permission] || fallback || permission;
export const canDo = (auth, permission) => (
    (auth?.roles || []).includes('super_admin') || (auth?.permissions || []).includes(permission)
);

export const money = (value, label = 'ج.م') => `${Number(value || 0).toFixed(0)} ${label}`;

export const initial = (name = '') => (name.trim().charAt(0) || 'ت');
