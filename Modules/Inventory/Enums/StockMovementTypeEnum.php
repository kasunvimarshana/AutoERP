<?php

declare(strict_types=1);

namespace Modules\Inventory\Enums;

/**
 * Stock Movement Type Enum
 *
 * Types of inventory stock movements.
 *
 * @package Modules\Inventory\Enums
 */
enum StockMovementTypeEnum: string
{
    case RECEIPT = 'receipt';
    case ISSUE = 'issue';
    case TRANSFER = 'transfer';
    case ADJUSTMENT = 'adjustment';
    case RETURN = 'return';
    case SCRAP = 'scrap';
    case PRODUCTION = 'production';
    case CONSUMPTION = 'consumption';
    case CYCLE_COUNT = 'cycle_count';

    /**
     * Get all movement type values
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get human-readable label
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::RECEIPT => 'Goods Receipt',
            self::ISSUE => 'Goods Issue',
            self::TRANSFER => 'Stock Transfer',
            self::ADJUSTMENT => 'Inventory Adjustment',
            self::RETURN => 'Stock Return',
            self::SCRAP => 'Scrap/Waste',
            self::PRODUCTION => 'Production Receipt',
            self::CONSUMPTION => 'Production Consumption',
            self::CYCLE_COUNT => 'Cycle Count Adjustment',
        };
    }

    /**
     * Check if movement increases stock
     *
     * @return bool
     */
    public function increasesStock(): bool
    {
        return in_array($this, [
            self::RECEIPT,
            self::RETURN,
            self::PRODUCTION,
        ], true);
    }

    /**
     * Check if movement decreases stock
     *
     * @return bool
     */
    public function decreasesStock(): bool
    {
        return in_array($this, [
            self::ISSUE,
            self::SCRAP,
            self::CONSUMPTION,
        ], true);
    }

    /**
     * Check if movement requires approval
     *
     * @return bool
     */
    public function requiresApproval(): bool
    {
        return in_array($this, [
            self::ADJUSTMENT,
            self::SCRAP,
        ], true);
    }

    /**
     * Get transaction type (positive or negative)
     *
     * @return int
     */
    public function transactionSign(): int
    {
        return $this->increasesStock() ? 1 : ($this->decreasesStock() ? -1 : 0);
    }
}
