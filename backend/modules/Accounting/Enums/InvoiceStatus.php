<?php

declare(strict_types=1);

namespace Modules\Accounting\Enums;

enum InvoiceStatus: string
{
    case DRAFT = 'draft';
    case SENT = 'sent';
    case PAID = 'paid';
    case OVERDUE = 'overdue';
    case CANCELLED = 'cancelled';
    case PARTIAL = 'partial';

    /**
     * Get a human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::SENT => 'Sent',
            self::PAID => 'Paid',
            self::OVERDUE => 'Overdue',
            self::CANCELLED => 'Cancelled',
            self::PARTIAL => 'Partially Paid',
        };
    }

    /**
     * Check if the invoice can be edited.
     */
    public function canEdit(): bool
    {
        return in_array($this, [self::DRAFT]);
    }

    /**
     * Check if the invoice can be cancelled.
     */
    public function canCancel(): bool
    {
        return in_array($this, [self::DRAFT, self::SENT, self::OVERDUE, self::PARTIAL]);
    }

    /**
     * Check if the invoice can receive payments.
     */
    public function canReceivePayment(): bool
    {
        return in_array($this, [self::SENT, self::OVERDUE, self::PARTIAL]);
    }
}
