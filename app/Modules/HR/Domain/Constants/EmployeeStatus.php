<?php

declare(strict_types=1);

namespace Modules\HR\Domain\Constants;

final class EmployeeStatus
{
    public const DRAFT = 'draft';
    public const ACTIVE = 'active';
    public const INACTIVE = 'inactive';
    public const ON_LEAVE = 'on_leave';
    public const SUSPENDED = 'suspended';
    public const TERMINATED = 'terminated';
    public const RESIGNED = 'resigned';
    public const ARCHIVED = 'archived';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [
            self::DRAFT,
            self::ACTIVE,
            self::INACTIVE,
            self::ON_LEAVE,
            self::SUSPENDED,
            self::TERMINATED,
            self::RESIGNED,
            self::ARCHIVED,
        ];
    }
}
