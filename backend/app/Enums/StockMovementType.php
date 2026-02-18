<?php

namespace App\Enums;

/**
 * Stock Movement Type Enum
 *
 * Defines the types of stock movements for the append-only stock ledger.
 */
enum StockMovementType: string
{
    case PURCHASE = 'purchase';
    case SALE = 'sale';
    case ADJUSTMENT_IN = 'adjustment_in';
    case ADJUSTMENT_OUT = 'adjustment_out';
    case TRANSFER_IN = 'transfer_in';
    case TRANSFER_OUT = 'transfer_out';
    case RETURN_IN = 'return_in';
    case RETURN_OUT = 'return_out';
    case PRODUCTION_IN = 'production_in';
    case PRODUCTION_OUT = 'production_out';
    case DAMAGE = 'damage';
    case LOSS = 'loss';

    /**
     * Check if movement increases stock
     */
    public function isIncrease(): bool
    {
        return in_array($this, [
            self::PURCHASE,
            self::ADJUSTMENT_IN,
            self::TRANSFER_IN,
            self::RETURN_IN,
            self::PRODUCTION_IN,
        ]);
    }

    /**
     * Check if movement decreases stock
     */
    public function isDecrease(): bool
    {
        return in_array($this, [
            self::SALE,
            self::ADJUSTMENT_OUT,
            self::TRANSFER_OUT,
            self::RETURN_OUT,
            self::PRODUCTION_OUT,
            self::DAMAGE,
            self::LOSS,
        ]);
    }

    /**
     * Get the sign for quantity calculation
     */
    public function getSign(): int
    {
        return $this->isIncrease() ? 1 : -1;
    }

    /**
     * Get display label
     */
    public function label(): string
    {
        return match ($this) {
            self::PURCHASE => 'Purchase',
            self::SALE => 'Sale',
            self::ADJUSTMENT_IN => 'Adjustment In',
            self::ADJUSTMENT_OUT => 'Adjustment Out',
            self::TRANSFER_IN => 'Transfer In',
            self::TRANSFER_OUT => 'Transfer Out',
            self::RETURN_IN => 'Return In',
            self::RETURN_OUT => 'Return Out',
            self::PRODUCTION_IN => 'Production In',
            self::PRODUCTION_OUT => 'Production Out',
            self::DAMAGE => 'Damage',
            self::LOSS => 'Loss',
        };
    }

    /**
     * Get all values as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
