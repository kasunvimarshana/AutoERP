<?php

declare(strict_types=1);

namespace Modules\Tenant\Constants;

final class TenantSubscriptionEventType
{
    public const ASSIGNED = 'assigned';
    public const RENEWED = 'renewed';
    public const EXTENDED = 'extended';
    public const CORRECTED = 'corrected';
    public const CANCELLED = 'cancelled';
    public const EXPIRED = 'expired';

    /** @return list<string> */
    public static function values(): array
    {
        return [
            self::ASSIGNED,
            self::RENEWED,
            self::EXTENDED,
            self::CORRECTED,
            self::CANCELLED,
            self::EXPIRED,
        ];
    }

    private function __construct() {}
}
