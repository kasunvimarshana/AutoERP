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

final class UserOrganizationUnitStatus
{
    public const ACTIVE = 'active';

    public const INACTIVE = 'inactive';

    public const REVOKED = 'revoked';

    public const DEFAULT_MARKER = 'default';

    /** @return list<string> */
    public static function values(): array
    {
        return [self::ACTIVE, self::INACTIVE, self::REVOKED];
    }

    private function __construct() {}
}
