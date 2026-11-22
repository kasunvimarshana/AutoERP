<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Tenant\Constants;

final class TenantDomainStatus
{
    public const PENDING = 'pending';
    public const ACTIVE = 'active';
    public const DISABLED = 'disabled';

    /** @return list<string> */
    public static function values(): array
    {
        return [self::PENDING, self::ACTIVE, self::DISABLED];
    }

    private function __construct() {}
}
