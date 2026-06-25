<?php

declare(strict_types=1);

namespace Modules\Tenant\Constants;

final class TenantSubscriptionStatus
{
    public const TRIAL = 'trial';
    public const ACTIVE = 'active';
    public const EXPIRED = 'expired';
    public const CANCELLED = 'cancelled';

    /** @return list<string> */
    public static function values(): array
    {
        return [self::TRIAL, self::ACTIVE, self::EXPIRED, self::CANCELLED];
    }

    /** @return list<string> */
    public static function assignable(): array
    {
        return [self::TRIAL, self::ACTIVE];
    }

    private function __construct() {}
}
