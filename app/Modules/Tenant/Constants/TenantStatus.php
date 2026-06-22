<?php

declare(strict_types=1);

namespace Modules\Tenant\Constants;

final class TenantStatus
{
    public const DRAFT = 'draft';
    public const ACTIVE = 'active';
    public const INACTIVE = 'inactive';
    public const SUSPENDED = 'suspended';
    public const ARCHIVED = 'archived';

    /** @return list<string> */
    public static function values(): array
    {
        return [self::DRAFT, self::ACTIVE, self::INACTIVE, self::SUSPENDED, self::ARCHIVED];
    }

    public static function isValid(string $status): bool
    {
        return in_array($status, self::values(), true);
    }

    public static function allowsRuntimeAccess(string $status): bool
    {
        return $status === self::ACTIVE;
    }

    private function __construct() {}
}
