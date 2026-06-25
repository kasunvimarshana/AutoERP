<?php

declare(strict_types=1);

namespace Modules\Configuration\Constants;

final class ConfigurationRevisionOperation
{
    public const CREATED = 'created';
    public const UPDATED = 'updated';
    public const REMOVED = 'removed';
    public const ROLLED_BACK = 'rolled_back';

    /** @return list<string> */
    public static function values(): array
    {
        return [self::CREATED, self::UPDATED, self::REMOVED, self::ROLLED_BACK];
    }

    private function __construct() {}
}
