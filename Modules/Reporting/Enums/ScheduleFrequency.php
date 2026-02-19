<?php

declare(strict_types=1);

namespace Modules\Reporting\Enums;

enum ScheduleFrequency: string
{
    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case MONTHLY = 'monthly';
    case QUARTERLY = 'quarterly';
    case YEARLY = 'yearly';

    public function label(): string
    {
        return match ($this) {
            self::DAILY => 'Daily',
            self::WEEKLY => 'Weekly',
            self::MONTHLY => 'Monthly',
            self::QUARTERLY => 'Quarterly',
            self::YEARLY => 'Yearly',
        };
    }

    public function cronExpression(): string
    {
        return match ($this) {
            self::DAILY => '0 0 * * *',
            self::WEEKLY => '0 0 * * 0',
            self::MONTHLY => '0 0 1 * *',
            self::QUARTERLY => '0 0 1 */3 *',
            self::YEARLY => '0 0 1 1 *',
        };
    }
}
