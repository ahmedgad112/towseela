<?php

namespace App\Enums;

enum AuditAction: string
{
    case UserCreated = 'user.created';
    case UserUpdated = 'user.updated';
    case UserDeleted = 'user.deleted';
    case PermissionsChanged = 'permissions.changed';
    case BusinessCreated = 'business.created';
    case BusinessUpdated = 'business.updated';
    case ProductCreated = 'product.created';
    case ProductUpdated = 'product.updated';
    case CouponCreated = 'coupon.created';
    case CouponUpdated = 'coupon.updated';
    case DeliveryZoneCreated = 'delivery_zone.created';
    case DeliveryZoneDeleted = 'delivery_zone.deleted';
    case DeliveryFeeUpdated = 'delivery_fee.updated';
    case DeliveryFeeDeleted = 'delivery_fee.deleted';
    case OrderConfirmed = 'order.confirmed';
    case OrderAssigned = 'order.assigned';
    case OrderStatusChanged = 'order.status_changed';
    case OrderCancelled = 'order.cancelled';
    case OrderDeleted = 'order.deleted';
    case OrderFailed = 'order.failed';
    case SettingsUpdated = 'settings.updated';
    case RoleCreated = 'role.created';
    case RoleUpdated = 'role.updated';
    case RoleDeleted = 'role.deleted';
    case PermissionCreated = 'permission.created';
    case PermissionDeleted = 'permission.deleted';

    public function label(): string
    {
        return match ($this) {
            self::UserCreated => 'إنشاء مستخدم',
            self::UserUpdated => 'تعديل مستخدم',
            self::UserDeleted => 'حذف مستخدم',
            self::PermissionsChanged => 'تغيير صلاحيات',
            self::BusinessCreated => 'إنشاء متجر',
            self::BusinessUpdated => 'تعديل متجر',
            self::ProductCreated => 'إنشاء منتج',
            self::ProductUpdated => 'تعديل منتج',
            self::CouponCreated => 'إنشاء عرض',
            self::CouponUpdated => 'تعديل عرض',
            self::DeliveryZoneCreated => 'إنشاء منطقة توصيل',
            self::DeliveryZoneDeleted => 'حذف منطقة توصيل',
            self::DeliveryFeeUpdated => 'تحديث رسوم التوصيل',
            self::DeliveryFeeDeleted => 'حذف قاعدة رسوم',
            self::OrderConfirmed => 'تأكيد طلب',
            self::OrderAssigned => 'تعيين مندوب',
            self::OrderStatusChanged => 'تغيير حالة طلب',
            self::OrderCancelled => 'إلغاء طلب',
            self::OrderDeleted => 'حذف طلب',
            self::OrderFailed => 'فشل تسليم',
            self::SettingsUpdated => 'تحديث الإعدادات',
            self::RoleCreated => 'إنشاء دور',
            self::RoleUpdated => 'تعديل دور',
            self::RoleDeleted => 'حذف دور',
            self::PermissionCreated => 'إنشاء صلاحية',
            self::PermissionDeleted => 'حذف صلاحية',
        };
    }
}
