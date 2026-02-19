<?php

declare(strict_types=1);

namespace Modules\Purchase\Enums;

/**
 * Goods Receipt Status Enum
 *
 * Represents the status of goods received from vendors.
 */
enum GoodsReceiptStatus: string
{
    case DRAFT = 'draft';
    case CONFIRMED = 'confirmed';
    case POSTED = 'posted';
    case CANCELLED = 'cancelled';

    /**
     * Get human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::CONFIRMED => 'Confirmed',
            self::POSTED => 'Posted to Inventory',
            self::CANCELLED => 'Cancelled',
        };
    }

    /**
     * Get color class for UI representation.
     */
    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::CONFIRMED => 'blue',
            self::POSTED => 'green',
            self::CANCELLED => 'red',
        };
    }

    /**
     * Check if receipt can be edited.
     */
    public function isEditable(): bool
    {
        return $this === self::DRAFT;
    }

    /**
     * Check if receipt can be posted to inventory.
     */
    public function canPostToInventory(): bool
    {
        return $this === self::CONFIRMED;
    }
}
