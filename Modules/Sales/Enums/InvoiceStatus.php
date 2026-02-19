<?php

declare(strict_types=1);

namespace Modules\Sales\Enums;

/**
 * Invoice Status Enum
 *
 * Represents the payment status of a sales invoice.
 */
enum InvoiceStatus: string
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
     * Get human-readable label.
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
     * Check if invoice can be modified.
     */
    public function canModify(): bool
    {
        return $this === self::DRAFT;
    }

    /**
     * Check if invoice can be sent.
     */
    public function canSend(): bool
    {
        return $this === self::DRAFT;
    }

    /**
     * Check if invoice can receive payment.
     */
    public function canReceivePayment(): bool
    {
        return in_array($this, [self::UNPAID, self::PARTIALLY_PAID, self::OVERDUE, self::SENT]);
    }

    /**
     * Check if invoice is fully paid.
     */
    public function isPaid(): bool
    {
        return $this === self::PAID;
    }

    /**
     * Check if invoice is overdue.
     */
    public function isOverdue(): bool
    {
        return $this === self::OVERDUE;
    }

    /**
     * Check if invoice is in a final state.
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::PAID, self::CANCELLED, self::REFUNDED]);
    }
}
