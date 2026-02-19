<?php

declare(strict_types=1);

namespace Modules\Pricing\Enums;

/**
 * Price List Status Enum
 *
 * Defines the status of a price list
 */
enum PriceListStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case SCHEDULED = 'scheduled';
    case EXPIRED = 'expired';

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
            self::SCHEDULED => 'Scheduled',
            self::EXPIRED => 'Expired',
        };
    }

    /**
     * Check if price list is active
     */
    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Check if price list can be used
     */
    public function canBeUsed(): bool
    {
        return in_array($this, [self::ACTIVE, self::SCHEDULED]);
    }
}
