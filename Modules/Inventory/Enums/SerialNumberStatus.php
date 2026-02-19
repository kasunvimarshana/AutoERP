<?php

declare(strict_types=1);

namespace Modules\Inventory\Enums;

/**
 * SerialNumberStatus Enum
 *
 * Defines status values for serial number tracking.
 */
enum SerialNumberStatus: string
{
    case IN_STOCK = 'in_stock';
    case RESERVED = 'reserved';
    case SOLD = 'sold';
    case RETURNED = 'returned';
    case SCRAPPED = 'scrapped';
    case IN_TRANSIT = 'in_transit';

    /**
     * Get human-readable label for the enum value.
     */
    public function label(): string
    {
        return match ($this) {
            self::IN_STOCK => 'In Stock',
            self::RESERVED => 'Reserved',
            self::SOLD => 'Sold',
            self::RETURNED => 'Returned',
            self::SCRAPPED => 'Scrapped',
            self::IN_TRANSIT => 'In Transit',
        };
    }

    /**
     * Check if serial number is available.
     */
    public function isAvailable(): bool
    {
        return $this === self::IN_STOCK;
    }

    /**
     * Check if serial number can be sold.
     */
    public function canBeSold(): bool
    {
        return in_array($this, [self::IN_STOCK, self::RETURNED], true);
    }
}
