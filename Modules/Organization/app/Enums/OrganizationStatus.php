<?php

declare(strict_types=1);

namespace Modules\Organization\Enums;

/**
 * Organization Status Enum
 *
 * Defines the status states for organizations
 */
enum OrganizationStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case SUSPENDED = 'suspended';

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
            self::SUSPENDED => 'Suspended',
        };
    }

    /**
     * Get description
     */
    public function description(): string
    {
        return match ($this) {
            self::ACTIVE => 'Organization is active and operational',
            self::INACTIVE => 'Organization is temporarily inactive',
            self::SUSPENDED => 'Organization has been suspended',
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
            self::SUSPENDED => 'danger',
        };
    }
}
