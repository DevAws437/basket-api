<?php

namespace App\Enums;

enum PeriodType: string
{
    case Q1 = 'Q1';
    case Q2 = 'Q2';
    case Q3 = 'Q3';
    case Q4 = 'Q4';
    case OT = 'OT';

    public function label(): string
    {
        return match ($this) {
            self::Q1 => 'الربع الأول',
            self::Q2 => 'الربع الثاني',
            self::Q3 => 'الربع الثالث',
            self::Q4 => 'الربع الرابع',
            self::OT => 'شوط إضافي',
        };
    }
}
