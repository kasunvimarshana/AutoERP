<?php

declare(strict_types=1);

namespace Modules\Auth\Constants;

final class RegistrationMode
{
    public const DISABLED = 'disabled';
    public const INVITE_ONLY = 'invite_only';

    /** @return list<string> */
    public static function values(): array
    {
        return [self::DISABLED, self::INVITE_ONLY];
    }

    private function __construct() {}
}
