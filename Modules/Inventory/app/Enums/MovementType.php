<?php

declare(strict_types=1);

namespace Modules\Inventory\Enums;

/**
 * Stock Movement Type Enum
 *
 * Defines the types of stock movements in the inventory system
 */
enum MovementType: string
{
    case IN = 'in';
    case OUT = 'out';
    case TRANSFER = 'transfer';
    case ADJUSTMENT = 'adjustment';

    /**
     * Get all available values
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
            self::IN => 'Stock In',
            self::OUT => 'Stock Out',
            self::TRANSFER => 'Transfer',
            self::ADJUSTMENT => 'Adjustment',
        };
    }
}
