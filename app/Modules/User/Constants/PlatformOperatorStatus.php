<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\User\Constants;

final class PlatformOperatorStatus
{
    public const ACTIVE = 'active';

    public const INACTIVE = 'inactive';

    /** @return list<string> */
    public static function values(): array
    {
        return [self::ACTIVE, self::INACTIVE];
    }

    private function __construct() {}
}
