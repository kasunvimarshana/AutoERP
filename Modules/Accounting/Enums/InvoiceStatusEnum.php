<?php

declare(strict_types=1);

namespace Modules\Accounting\Enums;

/**
 * Invoice Status Enum
 *
 * Lifecycle statuses for customer invoices.
 *
 * @package Modules\Accounting\Enums
 */
enum InvoiceStatusEnum: string
{
    case DRAFT = 'draft';
    case SENT = 'sent';
    case PARTIALLY_PAID = 'partially_paid';
    case PAID = 'paid';
    case OVERDUE = 'overdue';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';
    case WRITTEN_OFF = 'written_off';

    /**
     * Get all status values
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
            self::DRAFT => 'Draft',
            self::SENT => 'Sent',
            self::PARTIALLY_PAID => 'Partially Paid',
            self::PAID => 'Paid',
            self::OVERDUE => 'Overdue',
            self::CANCELLED => 'Cancelled',
            self::REFUNDED => 'Refunded',
            self::WRITTEN_OFF => 'Written Off',
        };
    }

    /**
     * Check if invoice can be modified
     *
     * @return bool
     */
    public function isEditable(): bool
    {
        return $this === self::DRAFT;
    }

    /**
     * Check if invoice is final (cannot be changed)
     *
     * @return bool
     */
    public function isFinal(): bool
    {
        return in_array($this, [
            self::PAID,
            self::CANCELLED,
            self::REFUNDED,
            self::WRITTEN_OFF,
        ], true);
    }

    /**
     * Check if payment can be received
     *
     * @return bool
     */
    public function canReceivePayment(): bool
    {
        return in_array($this, [
            self::SENT,
            self::PARTIALLY_PAID,
            self::OVERDUE,
        ], true);
    }

    /**
     * Get color for UI representation
     *
     * @return string
     */
    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::SENT => 'blue',
            self::PARTIALLY_PAID => 'yellow',
            self::PAID => 'green',
            self::OVERDUE => 'red',
            self::CANCELLED => 'gray',
            self::REFUNDED => 'orange',
            self::WRITTEN_OFF => 'red',
        };
    }
}
