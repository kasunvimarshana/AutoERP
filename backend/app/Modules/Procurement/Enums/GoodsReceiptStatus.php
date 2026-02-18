<?php

namespace App\Modules\Procurement\Enums;

/**
 * Goods Receipt Status Enum
 *
 * Represents the various states of a goods receipt note
 */
enum GoodsReceiptStatus: string
{
    case DRAFT = 'draft';
    case RECEIVED = 'received';
    case PARTIALLY_RECEIVED = 'partially_received';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    /**
     * Get a human-readable label for the status
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::RECEIVED => 'Received',
            self::PARTIALLY_RECEIVED => 'Partially Received',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        };
    }

    /**
     * Check if the GRN can be edited
     */
    public function isEditable(): bool
    {
        return in_array($this, [self::DRAFT, self::RECEIVED, self::PARTIALLY_RECEIVED]);
    }

    /**
     * Check if the GRN is finalized
     */
    public function isFinalized(): bool
    {
        return in_array($this, [self::COMPLETED, self::CANCELLED]);
    }
}
