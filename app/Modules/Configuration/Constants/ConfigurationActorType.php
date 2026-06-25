<?php

declare(strict_types=1);

namespace Modules\Configuration\Constants;

final class ConfigurationActorType
{
    public const SYSTEM = 'system';
    public const PLATFORM_OPERATOR = 'platform_operator';
    public const TENANT_USER = 'tenant_user';

    /** @return list<string> */
    public static function values(): array
    {
        return [self::SYSTEM, self::PLATFORM_OPERATOR, self::TENANT_USER];
    }

    private function __construct() {}
}
