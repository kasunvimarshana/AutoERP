<?php

declare(strict_types=1);

namespace Modules\UOM\Constants;

final class UomPermission
{
    public const VIEW = 'uom.view';
    public const MANAGE = 'uom.manage';
    public const CONVERT = 'uom.convert';

    /** @return array<string, string> */
    public static function descriptions(): array
    {
        return [
            self::VIEW => 'View units of measure and conversion definitions.',
            self::MANAGE => 'Create and maintain units of measure and conversion definitions.',
            self::CONVERT => 'Convert quantities using governed UOM definitions.',
        ];
    }
}
