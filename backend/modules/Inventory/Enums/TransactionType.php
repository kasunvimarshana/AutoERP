<?php

declare(strict_types=1);

namespace Modules\Inventory\Enums;

/**
 * Stock Transaction Type Enum
 *
 * Defines all possible stock transaction types in the system.
 */
enum TransactionType: string
{
    // Inbound Transactions (increase stock)
    case PURCHASE_RECEIPT = 'purchase_receipt';
    case STOCK_RETURN = 'stock_return';
    case ADJUSTMENT_IN = 'adjustment_in';
    case TRANSFER_IN = 'transfer_in';
    case PRODUCTION_IN = 'production_in';
    case OPENING_STOCK = 'opening_stock';

    // Outbound Transactions (decrease stock)
    case SALES_ORDER = 'sales_order';
    case SALES_INVOICE = 'sales_invoice';
    case CUSTOMER_RETURN = 'customer_return';
    case ADJUSTMENT_OUT = 'adjustment_out';
    case TRANSFER_OUT = 'transfer_out';
    case PRODUCTION_OUT = 'production_out';
    case DAMAGE = 'damage';
    case DISPOSAL = 'disposal';

    // Status Transactions (no net change)
    case RESERVATION = 'reservation';
    case ALLOCATION = 'allocation';
    case RELEASE = 'release';

    /**
     * Get all available transaction types
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get display label for the transaction type
     */
    public function label(): string
    {
        return match ($this) {
            self::PURCHASE_RECEIPT => 'Purchase Receipt',
            self::STOCK_RETURN => 'Stock Return',
            self::ADJUSTMENT_IN => 'Adjustment In',
            self::TRANSFER_IN => 'Transfer In',
            self::PRODUCTION_IN => 'Production In',
            self::OPENING_STOCK => 'Opening Stock',
            self::SALES_ORDER => 'Sales Order',
            self::SALES_INVOICE => 'Sales Invoice',
            self::CUSTOMER_RETURN => 'Customer Return',
            self::ADJUSTMENT_OUT => 'Adjustment Out',
            self::TRANSFER_OUT => 'Transfer Out',
            self::PRODUCTION_OUT => 'Production Out',
            self::DAMAGE => 'Damage',
            self::DISPOSAL => 'Disposal',
            self::RESERVATION => 'Reservation',
            self::ALLOCATION => 'Allocation',
            self::RELEASE => 'Release',
        };
    }

    /**
     * Get the quantity multiplier for this transaction type
     * Positive for inbound, negative for outbound
     */
    public function multiplier(): int
    {
        return match ($this) {
            self::PURCHASE_RECEIPT,
            self::STOCK_RETURN,
            self::ADJUSTMENT_IN,
            self::TRANSFER_IN,
            self::PRODUCTION_IN,
            self::OPENING_STOCK => 1,

            self::SALES_ORDER,
            self::SALES_INVOICE,
            self::CUSTOMER_RETURN,
            self::ADJUSTMENT_OUT,
            self::TRANSFER_OUT,
            self::PRODUCTION_OUT,
            self::DAMAGE,
            self::DISPOSAL => -1,

            self::RESERVATION,
            self::ALLOCATION,
            self::RELEASE => 0,
        };
    }

    /**
     * Check if transaction type is inbound
     */
    public function isInbound(): bool
    {
        return $this->multiplier() > 0;
    }

    /**
     * Check if transaction type is outbound
     */
    public function isOutbound(): bool
    {
        return $this->multiplier() < 0;
    }
}
