<?php

declare(strict_types=1);

namespace Modules\User\Constants;

final class PlatformOperatorStatus
{
    public const INVITED = 'invited';
    public const ACTIVE = 'active';
    public const INACTIVE = 'inactive';

    /** @return list<string> */
    public static function values(): array
    {
        return [self::INVITED, self::ACTIVE, self::INACTIVE];
    }

    private function __construct() {}
}
