<?php

declare(strict_types=1);

namespace Modules\Auth\Constants;

final class PlatformMfaStatus
{
    public const PENDING = 'pending';
    public const ACTIVE = 'active';
    public const DISABLED = 'disabled';

    /** @return list<string> */
    public static function values(): array
    {
        return [self::PENDING, self::ACTIVE, self::DISABLED];
    }

    private function __construct() {}
}
