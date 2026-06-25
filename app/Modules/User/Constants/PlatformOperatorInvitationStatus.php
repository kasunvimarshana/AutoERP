<?php

declare(strict_types=1);

namespace Modules\User\Constants;

final class PlatformOperatorInvitationStatus
{
    public const PENDING = 'pending';
    public const ACCEPTED = 'accepted';
    public const REVOKED = 'revoked';
    public const EXPIRED = 'expired';

    /** @return list<string> */
    public static function values(): array
    {
        return [self::PENDING, self::ACCEPTED, self::REVOKED, self::EXPIRED];
    }

    private function __construct() {}
}
