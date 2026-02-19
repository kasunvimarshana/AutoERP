<?php

declare(strict_types=1);

namespace Modules\Purchase\Enums;

/**
 * Bill Status Enum
 *
 * Represents the payment status of a vendor bill.
 */
enum BillStatus: string
{
    case DRAFT = 'draft';
    case SENT = 'sent';
    case UNPAID = 'unpaid';
    case PARTIALLY_PAID = 'partially_paid';
    case PAID = 'paid';
    case OVERDUE = 'overdue';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';

    /**
     * Get human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::SENT => 'Sent',
            self::UNPAID => 'Unpaid',
            self::PARTIALLY_PAID => 'Partially Paid',
            self::PAID => 'Paid',
            self::OVERDUE => 'Overdue',
            self::CANCELLED => 'Cancelled',
            self::REFUNDED => 'Refunded',
        };
    }

    /**
     * Get color class for UI representation.
     */
    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::SENT => 'blue',
            self::UNPAID => 'yellow',
            self::PARTIALLY_PAID => 'orange',
            self::PAID => 'green',
            self::OVERDUE => 'red',
            self::CANCELLED => 'gray',
            self::REFUNDED => 'purple',
        };
    }

    /**
     * Check if bill can be edited.
     */
    public function isEditable(): bool
    {
        return in_array($this, [self::DRAFT], true);
    }

    /**
     * Check if bill can be cancelled.
     */
    public function isCancellable(): bool
    {
        return ! in_array($this, [self::PAID, self::CANCELLED, self::REFUNDED], true);
    }

    /**
     * Check if bill can accept payments.
     */
    public function canAcceptPayment(): bool
    {
        return in_array($this, [self::SENT, self::UNPAID, self::PARTIALLY_PAID, self::OVERDUE], true);
    }
}
