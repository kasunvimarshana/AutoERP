<?php

declare(strict_types=1);

namespace Modules\User\Constants;

final class UserDevicePlatform
{
    public const IOS = 'ios';
    public const ANDROID = 'android';
    public const WEB = 'web';

    /** @return list<string> */
    public static function values(): array
    {
        return [self::IOS, self::ANDROID, self::WEB];
    }

    private function __construct() {}
}
