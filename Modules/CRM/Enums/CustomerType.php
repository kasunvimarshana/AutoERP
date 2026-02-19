<?php

declare(strict_types=1);

namespace Modules\CRM\Enums;

enum CustomerType: string
{
    case INDIVIDUAL = 'individual';
    case BUSINESS = 'business';
    case GOVERNMENT = 'government';
    case NON_PROFIT = 'non_profit';

    public function label(): string
    {
        return match ($this) {
            self::INDIVIDUAL => 'Individual',
            self::BUSINESS => 'Business',
            self::GOVERNMENT => 'Government',
            self::NON_PROFIT => 'Non-Profit Organization',
        };
    }
}
