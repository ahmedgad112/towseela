<?php

namespace App\Enums;

enum OrderStatus: string
{
    case New = 'new';
    case PendingConfirmation = 'pending_confirmation';
    case Confirmed = 'confirmed';
    case Preparing = 'preparing';
    case AwaitingDriver = 'awaiting_driver';
    case AssignedToDriver = 'assigned_to_driver';
    case PickedUp = 'picked_up';
    case OutForDelivery = 'out_for_delivery';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    case DeliveryFailed = 'delivery_failed';
    case Returned = 'returned';

    public function label(): string
    {
        return match ($this) {
            self::New => 'جديد',
            self::PendingConfirmation => 'بانتظار التأكيد',
            self::Confirmed => 'مؤكد',
            self::Preparing => 'جارٍ التحضير',
            self::AwaitingDriver => 'بانتظار مندوب',
            self::AssignedToDriver => 'تم تعيين مندوب',
            self::PickedUp => 'تم الاستلام',
            self::OutForDelivery => 'في الطريق',
            self::Delivered => 'تم التسليم',
            self::Cancelled => 'ملغي',
            self::DeliveryFailed => 'فشل التسليم',
            self::Returned => 'مرتجع',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::New, self::PendingConfirmation => 'gold',
            self::Confirmed, self::Preparing => 'navy',
            self::AwaitingDriver => 'amber',
            self::AssignedToDriver, self::PickedUp => 'blue',
            self::OutForDelivery => 'indigo',
            self::Delivered => 'green',
            self::Cancelled, self::DeliveryFailed => 'red',
            self::Returned => 'slate',
        };
    }

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::New => [self::PendingConfirmation, self::Cancelled],
            self::PendingConfirmation => [self::Confirmed, self::Preparing, self::Cancelled],
            self::Confirmed => [self::Preparing, self::AwaitingDriver, self::Cancelled],
            self::Preparing => [self::AwaitingDriver, self::Cancelled],
            self::AwaitingDriver => [self::AssignedToDriver, self::Cancelled],
            self::AssignedToDriver => [self::PickedUp, self::AwaitingDriver, self::Cancelled],
            self::PickedUp => [self::OutForDelivery],
            self::OutForDelivery => [self::Delivered, self::DeliveryFailed],
            self::DeliveryFailed => [self::AwaitingDriver, self::Returned, self::Cancelled],
            self::Delivered, self::Cancelled, self::Returned => [],
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }

    public function isCustomerCancellable(): bool
    {
        return in_array($this, [self::New, self::PendingConfirmation], true);
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Delivered, self::Cancelled, self::Returned], true);
    }

    public function isActiveAssignment(): bool
    {
        return in_array($this, [
            self::AssignedToDriver,
            self::PickedUp,
            self::OutForDelivery,
        ], true);
    }

    /**
     * @return list<self>
     */
    public static function openStatuses(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $status): bool => ! $status->isTerminal(),
        ));
    }
}
