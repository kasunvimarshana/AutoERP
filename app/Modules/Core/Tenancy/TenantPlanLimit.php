<?php

declare(strict_types=1);

namespace Modules\Core\Tenancy;

final class TenantPlanLimit
{
    public const USERS = 'max_users';
    public const WAREHOUSES = 'max_warehouses';
    public const STORAGE_MEGABYTES = 'max_storage_mb';

    /** @return list<string> */
    public static function values(): array
    {
        return [self::USERS, self::WAREHOUSES, self::STORAGE_MEGABYTES];
    }

    private function __construct() {}
}
