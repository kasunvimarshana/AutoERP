<?php

declare(strict_types=1);

namespace Modules\Configuration\Constants;

final class ConfigurationValueType
{
    public const STRING = 'string';
    public const INTEGER = 'integer';
    public const DECIMAL = 'decimal';
    public const BOOLEAN = 'boolean';
    public const JSON = 'json';

    /** @return list<string> */
    public static function values(): array
    {
        return [self::STRING, self::INTEGER, self::DECIMAL, self::BOOLEAN, self::JSON];
    }

    private function __construct() {}
}
