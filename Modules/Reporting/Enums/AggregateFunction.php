<?php

declare(strict_types=1);

namespace Modules\Reporting\Enums;

enum AggregateFunction: string
{
    case SUM = 'sum';
    case AVG = 'avg';
    case COUNT = 'count';
    case MIN = 'min';
    case MAX = 'max';

    public function label(): string
    {
        return match ($this) {
            self::SUM => 'Sum',
            self::AVG => 'Average',
            self::COUNT => 'Count',
            self::MIN => 'Minimum',
            self::MAX => 'Maximum',
        };
    }
}
