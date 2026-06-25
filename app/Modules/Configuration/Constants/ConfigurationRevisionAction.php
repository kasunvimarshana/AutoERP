<?php

declare(strict_types=1);

namespace Modules\Configuration\Constants;

final class ConfigurationRevisionAction
{
    public const CREATED = 'created';
    public const UPDATED = 'updated';
    public const REMOVED = 'removed';

    /** @return list<string> */
    public static function values(): array
    {
        return [self::CREATED, self::UPDATED, self::REMOVED];
    }

    private function __construct() {}
}
