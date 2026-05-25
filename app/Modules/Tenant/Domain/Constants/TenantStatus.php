<?php

declare(strict_types=1);

namespace Modules\Tenant\Domain\Constants;

final class TenantStatus
{
    public const ACTIVE = 'active';
    public const INACTIVE = 'inactive';
    public const SUSPENDED = 'suspended';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [
            self::ACTIVE,
            self::INACTIVE,
            self::SUSPENDED,
        ];
    }

    public static function isValid(string $status): bool
    {
        return in_array($status, self::values(), true);
    }

    private function __construct()
    {
    }
}
