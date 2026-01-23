<?php

declare(strict_types=1);

namespace App\Core\Enums;

/**
 * User Status Enum
 *
 * Defines possible user account statuses
 */
enum UserStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case SUSPENDED = 'suspended';
    case PENDING = 'pending';
    case DELETED = 'deleted';

    /**
     * Get all enum values
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get enum label
     */
    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::SUSPENDED => 'Suspended',
            self::PENDING => 'Pending Verification',
            self::DELETED => 'Deleted',
        };
    }

    /**
     * Check if status is active
     */
    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Check if status allows login
     */
    public function canLogin(): bool
    {
        return in_array($this, [self::ACTIVE], true);
    }
}
