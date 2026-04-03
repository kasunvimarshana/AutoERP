<?php

declare(strict_types=1);

namespace Modules\Settings\Domain\ValueObjects;

class SettingType
{
    public const STRING  = 'string';
    public const INTEGER = 'integer';
    public const FLOAT   = 'float';
    public const BOOLEAN = 'boolean';
    public const JSON    = 'json';
    public const ARRAY   = 'array';

    public static function values(): array
    {
        return ['string', 'integer', 'float', 'boolean', 'json', 'array'];
    }
}
