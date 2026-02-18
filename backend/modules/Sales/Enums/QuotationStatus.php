<?php

declare(strict_types=1);

namespace Modules\Sales\Enums;

enum QuotationStatus: string
{
    case DRAFT = 'draft';
    case SENT = 'sent';
    case ACCEPTED = 'accepted';
    case REJECTED = 'rejected';
    case EXPIRED = 'expired';
    case CONVERTED = 'converted';

    /**
     * Get human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::SENT => 'Sent to Customer',
            self::ACCEPTED => 'Accepted',
            self::REJECTED => 'Rejected',
            self::EXPIRED => 'Expired',
            self::CONVERTED => 'Converted to Order',
        };
    }

    /**
     * Check if quotation can be edited.
     */
    public function canEdit(): bool
    {
        return $this === self::DRAFT;
    }

    /**
     * Check if quotation can be sent.
     */
    public function canSend(): bool
    {
        return $this === self::DRAFT;
    }

    /**
     * Check if quotation can be accepted or rejected.
     */
    public function canRespond(): bool
    {
        return $this === self::SENT;
    }

    /**
     * Check if quotation can be converted to order.
     */
    public function canConvert(): bool
    {
        return $this === self::ACCEPTED;
    }

    /**
     * Get color class for UI display.
     */
    public function colorClass(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::SENT => 'blue',
            self::ACCEPTED => 'green',
            self::REJECTED => 'red',
            self::EXPIRED => 'orange',
            self::CONVERTED => 'purple',
        };
    }
}
