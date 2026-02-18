<?php

declare(strict_types=1);

namespace Modules\Purchasing\Enums;

/**
 * Goods Receipt Status Enum
 *
 * Represents the various states of a goods receipt in the procurement process.
 */
enum GoodsReceiptStatus: string
{
    case DRAFT = 'draft';
    case RECEIVED = 'received';
    case INSPECTED = 'inspected';
    case ACCEPTED = 'accepted';
    case REJECTED = 'rejected';
    case PARTIALLY_ACCEPTED = 'partially_accepted';

    /**
     * Get the label for display
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::RECEIVED => 'Received',
            self::INSPECTED => 'Inspected',
            self::ACCEPTED => 'Accepted',
            self::REJECTED => 'Rejected',
            self::PARTIALLY_ACCEPTED => 'Partially Accepted',
        };
    }

    /**
     * Get all valid status values
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Check if status allows editing
     */
    public function isEditable(): bool
    {
        return $this === self::DRAFT;
    }

    /**
     * Check if status is final
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::ACCEPTED, self::REJECTED, self::PARTIALLY_ACCEPTED]);
    }

    /**
     * Get next possible statuses
     */
    public function nextStatuses(): array
    {
        return match ($this) {
            self::DRAFT => [self::RECEIVED],
            self::RECEIVED => [self::INSPECTED, self::ACCEPTED],
            self::INSPECTED => [self::ACCEPTED, self::REJECTED, self::PARTIALLY_ACCEPTED],
            default => []
        };
    }
}
