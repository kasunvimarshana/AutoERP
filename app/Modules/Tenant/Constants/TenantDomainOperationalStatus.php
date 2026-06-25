<?php

declare(strict_types=1);

namespace Modules\Tenant\Constants;

final class TenantDomainOperationalStatus
{
    public const PENDING = 'pending';
    public const CHECKING = 'checking';
    public const READY = 'ready';
    public const FAILED = 'failed';
    public const DISABLED = 'disabled';

    /** @return list<string> */
    public static function values(): array
    {
        return [self::PENDING, self::CHECKING, self::READY, self::FAILED, self::DISABLED];
    }

    private function __construct() {}
}
