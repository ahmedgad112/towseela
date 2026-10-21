<?php

namespace App\Enums;

enum CouponType: string
{
    case Fixed = 'fixed';
    case Percent = 'percent';

    public function label(): string
    {
        return match ($this) {
            self::Fixed => 'مبلغ ثابت',
            self::Percent => 'نسبة مئوية',
        };
    }
}
