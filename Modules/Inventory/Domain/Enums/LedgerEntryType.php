<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Enums;

enum LedgerEntryType: string
{
    case IN                = 'IN';
    case OUT               = 'OUT';
    case ADJUSTMENT_ADD    = 'ADJUSTMENT_ADD';
    case ADJUSTMENT_REMOVE = 'ADJUSTMENT_REMOVE';
    case TRANSFER_IN       = 'TRANSFER_IN';
    case TRANSFER_OUT      = 'TRANSFER_OUT';
    case SALE                       = 'SALE';
    case PURCHASE_RECEIPT           = 'PURCHASE_RECEIPT';
    case RETURN                     = 'RETURN';
    case MANUFACTURING_CONSUMPTION  = 'MANUFACTURING_CONSUMPTION';
    case MANUFACTURING_OUTPUT       = 'MANUFACTURING_OUTPUT';

    /**
     * Returns true for entry types that increase stock.
     */
    public function isInbound(): bool
    {
        return match ($this) {
            self::IN, self::ADJUSTMENT_ADD, self::TRANSFER_IN,
            self::PURCHASE_RECEIPT, self::RETURN,
            self::MANUFACTURING_OUTPUT => true,
            default                              => false,
        };
    }

    /**
     * Returns true for entry types that decrease stock.
     */
    public function isOutbound(): bool
    {
        return ! $this->isInbound();
    }

    public function label(): string
    {
        return match ($this) {
            self::IN                => 'Stock In',
            self::OUT               => 'Stock Out',
            self::ADJUSTMENT_ADD    => 'Adjustment (Add)',
            self::ADJUSTMENT_REMOVE => 'Adjustment (Remove)',
            self::TRANSFER_IN       => 'Transfer In',
            self::TRANSFER_OUT      => 'Transfer Out',
            self::SALE              => 'Sale',
            self::PURCHASE_RECEIPT  => 'Purchase Receipt',
            self::RETURN            => 'Return',
            self::MANUFACTURING_CONSUMPTION => 'Manufacturing Consumption',
            self::MANUFACTURING_OUTPUT      => 'Manufacturing Output',
        };
    }
}
