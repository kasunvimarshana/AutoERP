<?php

declare(strict_types=1);

namespace Modules\Sales\Enums;

/**
 * Quotation Status Enum
 *
 * Represents the lifecycle status of a sales quotation.
 */
enum QuotationStatus: string
{
    case DRAFT = 'draft';
    case SENT = 'sent';
    case ACCEPTED = 'accepted';
    case REJECTED = 'rejected';
    case EXPIRED = 'expired';
    case CONVERTED = 'converted';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::SENT => 'Sent',
            self::ACCEPTED => 'Accepted',
            self::REJECTED => 'Rejected',
            self::EXPIRED => 'Expired',
            self::CONVERTED => 'Converted to Order',
        };
    }

    /**
     * Check if quotation can be modified.
     */
    public function canModify(): bool
    {
        return in_array($this, [self::DRAFT, self::SENT]);
    }

    /**
     * Check if quotation can be sent.
     */
    public function canSend(): bool
    {
        return $this === self::DRAFT;
    }

    /**
     * Check if quotation can be converted to order.
     */
    public function canConvert(): bool
    {
        return $this === self::ACCEPTED;
    }

    /**
     * Check if quotation is in a final state.
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::ACCEPTED, self::REJECTED, self::EXPIRED, self::CONVERTED]);
    }
}
