<?php

declare(strict_types=1);

namespace Modules\Customer\Enums;

/**
 * Vehicle Status Enum
 *
 * Defines possible status values for vehicles
 */
enum VehicleStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case SOLD = 'sold';
    case SCRAPPED = 'scrapped';

    /**
     * Get all status values
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Check if status is active
     */
    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Get status label
     */
    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::SOLD => 'Sold',
            self::SCRAPPED => 'Scrapped',
        };
    }
}
