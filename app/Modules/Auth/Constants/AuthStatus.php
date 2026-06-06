<?php

declare(strict_types=1);

namespace Modules\Auth\Constants;

final class AuthStatus
{
    public const ACTIVE = 'active';

    public const INACTIVE = 'inactive';

    public const REVOKED = 'revoked';

    public const EXPIRED = 'expired';

    public const PENDING = 'pending';

    public const VERIFIED = 'verified';

    public const FAILED = 'failed';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [
            self::ACTIVE,
            self::INACTIVE,
            self::REVOKED,
            self::EXPIRED,
            self::PENDING,
            self::VERIFIED,
            self::FAILED,
        ];
    }

    private function __construct() {}
}
