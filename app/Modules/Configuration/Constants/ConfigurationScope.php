<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Configuration\Constants;

final class ConfigurationScope
{
    public const GLOBAL = 'global';
    public const TENANT = 'tenant';
    public const ORGANIZATION_UNIT = 'organization_unit';

    /** @return list<string> */
    public static function values(): array
    {
        return [self::GLOBAL, self::TENANT, self::ORGANIZATION_UNIT];
    }

    private function __construct() {}
}
