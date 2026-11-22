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

final class TenantDatabaseStrategy
{
    public const SHARED_SCHEMA = 'shared_schema';

    /** @return list<string> */
    public static function supported(): array
    {
        return [self::SHARED_SCHEMA];
    }
}
