<?php

declare(strict_types=1);

namespace Modules\Billing\Enums;

enum UsageType: string
{
    case Users = 'users';
    case Storage = 'storage';
    case ApiCalls = 'api_calls';
    case Transactions = 'transactions';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Users => 'Users',
            self::Storage => 'Storage (GB)',
            self::ApiCalls => 'API Calls',
            self::Transactions => 'Transactions',
            self::Custom => 'Custom',
        };
    }
}
