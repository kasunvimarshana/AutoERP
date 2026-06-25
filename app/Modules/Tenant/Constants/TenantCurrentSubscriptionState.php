<?php

declare(strict_types=1);

namespace Modules\Tenant\Constants;

final class TenantCurrentSubscriptionState
{
    public const ASSIGNED = 'assigned';
    public const CANCELLED = 'cancelled';
    public const EXPIRED = 'expired';

    /** @return list<string> */
    public static function values(): array
    {
        return [self::ASSIGNED, self::CANCELLED, self::EXPIRED];
    }

    private function __construct() {}
}
