<?php

declare(strict_types=1);

namespace Modules\CRM\Enums;

enum ContactType: string
{
    case PRIMARY = 'primary';
    case BILLING = 'billing';
    case SHIPPING = 'shipping';
    case TECHNICAL = 'technical';
    case SALES = 'sales';
    case SUPPORT = 'support';

    public function label(): string
    {
        return match ($this) {
            self::PRIMARY => 'Primary Contact',
            self::BILLING => 'Billing Contact',
            self::SHIPPING => 'Shipping Contact',
            self::TECHNICAL => 'Technical Contact',
            self::SALES => 'Sales Contact',
            self::SUPPORT => 'Support Contact',
        };
    }
}
