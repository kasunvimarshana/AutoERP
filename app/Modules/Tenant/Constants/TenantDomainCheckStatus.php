<?php

declare(strict_types=1);

namespace Modules\Tenant\Constants;

final class TenantDomainCheckStatus
{
    public const PENDING = 'pending';
    public const CHECKING = 'checking';
    public const READY = 'ready';
    public const FAILED = 'failed';

    /** @return list<string> */
    public static function values(): array
    {
        return [self::PENDING, self::CHECKING, self::READY, self::FAILED];
    }

    private function __construct() {}
}
