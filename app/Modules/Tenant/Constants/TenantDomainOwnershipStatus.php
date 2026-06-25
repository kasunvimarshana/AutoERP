<?php

declare(strict_types=1);

namespace Modules\Tenant\Constants;

final class TenantDomainOwnershipStatus
{
    public const PENDING = 'pending';
    public const CHECKING = 'checking';
    public const VERIFIED = 'verified';
    public const FAILED = 'failed';
    public const EXPIRED = 'expired';

    /** @return list<string> */
    public static function values(): array
    {
        return [self::PENDING, self::CHECKING, self::VERIFIED, self::FAILED, self::EXPIRED];
    }

    private function __construct() {}
}
