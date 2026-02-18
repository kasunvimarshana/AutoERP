<?php

declare(strict_types=1);

namespace Modules\Inventory\Enums;

/**
 * Serial Number Status Enum
 *
 * Defines the possible status values for serial-tracked items.
 */
enum SerialNumberStatus: string
{
    case IN_STOCK = 'in_stock';
    case RESERVED = 'reserved';
    case SOLD = 'sold';
    case IN_TRANSIT = 'in_transit';
    case IN_SERVICE = 'in_service';
    case DEFECTIVE = 'defective';
    case SCRAPPED = 'scrapped';
    case RETURNED = 'returned';

    /**
     * Get human-readable label for the status
     */
    public function label(): string
    {
        return match ($this) {
            self::IN_STOCK => 'In Stock',
            self::RESERVED => 'Reserved',
            self::SOLD => 'Sold',
            self::IN_TRANSIT => 'In Transit',
            self::IN_SERVICE => 'In Service',
            self::DEFECTIVE => 'Defective',
            self::SCRAPPED => 'Scrapped',
            self::RETURNED => 'Returned',
        };
    }

    /**
     * Get description for the status
     */
    public function description(): string
    {
        return match ($this) {
            self::IN_STOCK => 'Item is available in warehouse',
            self::RESERVED => 'Item is reserved for a sales order',
            self::SOLD => 'Item has been sold to a customer',
            self::IN_TRANSIT => 'Item is being transferred',
            self::IN_SERVICE => 'Item is being serviced or repaired',
            self::DEFECTIVE => 'Item has quality issues',
            self::SCRAPPED => 'Item has been disposed',
            self::RETURNED => 'Item has been returned by customer',
        };
    }

    /**
     * Check if status allows selling
     */
    public function canBeSold(): bool
    {
        return in_array($this, [
            self::IN_STOCK,
            self::RETURNED,
        ]);
    }

    /**
     * Check if status indicates item is available
     */
    public function isAvailable(): bool
    {
        return $this === self::IN_STOCK;
    }

    /**
     * Get all available statuses as array
     */
    public static function toArray(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }

    /**
     * Get all status options with labels
     */
    public static function options(): array
    {
        return array_map(
            fn($case) => ['value' => $case->value, 'label' => $case->label()],
            self::cases()
        );
    }
}
