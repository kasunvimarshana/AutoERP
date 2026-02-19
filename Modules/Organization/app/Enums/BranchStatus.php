<?php

declare(strict_types=1);

namespace Modules\Organization\Enums;

/**
 * Branch Status Enum
 *
 * Defines the status states for branches
 */
enum BranchStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case MAINTENANCE = 'maintenance';

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
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::MAINTENANCE => 'Under Maintenance',
        };
    }

    /**
     * Get description
     */
    public function description(): string
    {
        return match ($this) {
            self::ACTIVE => 'Branch is active and accepting customers',
            self::INACTIVE => 'Branch is temporarily inactive',
            self::MAINTENANCE => 'Branch is under maintenance',
        };
    }

    /**
     * Get color code for UI
     */
    public function color(): string
    {
        return match ($this) {
            self::ACTIVE => 'success',
            self::INACTIVE => 'warning',
            self::MAINTENANCE => 'info',
        };
    }
}
