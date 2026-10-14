<?php

declare(strict_types=1);

namespace Modules\Reporting\Enums;

enum ReceivableAgingBucket: string
{
    case Current = 'current';
    case Days1To30 = '1_30';
    case Days31To60 = '31_60';
    case Days61To90 = '61_90';
    case Over90 = '90_plus';

    public function label(): string
    {
        return match ($this) {
            self::Current => 'Current',
            self::Days1To30 => '1–30 days',
            self::Days31To60 => '31–60 days',
            self::Days61To90 => '61–90 days',
            self::Over90 => '90+ days',
        };
    }
}
