<?php

namespace App\Enums;

enum AddressLabel: string
{
    case Home = 'home';
    case Work = 'work';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Home => 'المنزل',
            self::Work => 'العمل',
            self::Other => 'آخر',
        };
    }
}
