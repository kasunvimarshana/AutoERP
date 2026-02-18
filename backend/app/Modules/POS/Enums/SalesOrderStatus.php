<?php

namespace App\Modules\POS\Enums;

enum SalesOrderStatus: string
{
    case DRAFT = 'draft';
    case QUOTATION = 'quotation';
    case CONFIRMED = 'confirmed';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case INVOICED = 'invoiced';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::QUOTATION => 'Quotation',
            self::CONFIRMED => 'Confirmed',
            self::IN_PROGRESS => 'In Progress',
            self::COMPLETED => 'Completed',
            self::INVOICED => 'Invoiced',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function canTransitionTo(self $newStatus): bool
    {
        return match ($this) {
            self::DRAFT => in_array($newStatus, [self::QUOTATION, self::CONFIRMED, self::CANCELLED]),
            self::QUOTATION => in_array($newStatus, [self::CONFIRMED, self::CANCELLED]),
            self::CONFIRMED => in_array($newStatus, [self::IN_PROGRESS, self::CANCELLED]),
            self::IN_PROGRESS => in_array($newStatus, [self::COMPLETED, self::CANCELLED]),
            self::COMPLETED => in_array($newStatus, [self::INVOICED]),
            self::INVOICED => false,
            self::CANCELLED => false,
        };
    }

    public function isEditable(): bool
    {
        return in_array($this, [self::DRAFT, self::QUOTATION]);
    }

    public function isCancellable(): bool
    {
        return ! in_array($this, [self::INVOICED, self::CANCELLED]);
    }
}
