<?php

declare(strict_types=1);

namespace Modules\SystemUser\Domain\Constants;

final class SystemUserStatus
{
    public const ACTIVE = 'active';
    public const INACTIVE = 'inactive';
    public const BLOCKED = 'blocked';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [
            self::ACTIVE,
            self::INACTIVE,
            self::BLOCKED,
        ];
    }

    public static function isValid(string $value): bool
    {
        return in_array($value, self::values(), true);
    }

    private function __construct()
    {
    }
}
