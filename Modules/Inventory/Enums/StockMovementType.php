<?php

declare(strict_types=1);

namespace Modules\Inventory\Enums;

/**
 * StockMovementType Enum
 *
 * Defines types of stock movements in the inventory system.
 */
enum StockMovementType: string
{
    case RECEIPT = 'receipt';
    case ISSUE = 'issue';
    case TRANSFER = 'transfer';
    case ADJUSTMENT = 'adjustment';
    case COUNT = 'count';
    case RETURN = 'return';
    case SCRAP = 'scrap';
    case RESERVED = 'reserved';
    case RELEASED = 'released';

    /**
     * Get human-readable label for the enum value.
     */
    public function label(): string
    {
        return match ($this) {
            self::RECEIPT => 'Receipt',
            self::ISSUE => 'Issue',
            self::TRANSFER => 'Transfer',
            self::ADJUSTMENT => 'Adjustment',
            self::COUNT => 'Physical Count',
            self::RETURN => 'Return',
            self::SCRAP => 'Scrap',
            self::RESERVED => 'Reserved',
            self::RELEASED => 'Released',
        };
    }

    /**
     * Get description for the enum value.
     */
    public function description(): string
    {
        return match ($this) {
            self::RECEIPT => 'Stock received into warehouse',
            self::ISSUE => 'Stock issued from warehouse',
            self::TRANSFER => 'Stock transferred between locations',
            self::ADJUSTMENT => 'Manual stock adjustment',
            self::COUNT => 'Physical inventory count adjustment',
            self::RETURN => 'Stock returned to warehouse',
            self::SCRAP => 'Stock scrapped/disposed',
            self::RESERVED => 'Stock reserved for order',
            self::RELEASED => 'Reserved stock released',
        };
    }

    /**
     * Check if this movement increases stock.
     */
    public function increasesStock(): bool
    {
        return match ($this) {
            self::RECEIPT, self::RETURN, self::RELEASED => true,
            default => false,
        };
    }

    /**
     * Check if this movement decreases stock.
     */
    public function decreasesStock(): bool
    {
        return match ($this) {
            self::ISSUE, self::SCRAP, self::RESERVED => true,
            default => false,
        };
    }
}
