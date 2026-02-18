<?php

namespace App\Modules\POS\Enums;

enum InvoiceStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case PARTIALLY_PAID = 'partially_paid';
    case PAID = 'paid';
    case OVERDUE = 'overdue';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PENDING => 'Pending',
            self::PARTIALLY_PAID => 'Partially Paid',
            self::PAID => 'Paid',
            self::OVERDUE => 'Overdue',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function canReceivePayment(): bool
    {
        return in_array($this, [self::PENDING, self::PARTIALLY_PAID, self::OVERDUE]);
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::PAID, self::CANCELLED]);
    }
}
