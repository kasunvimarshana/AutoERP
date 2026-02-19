<?php

declare(strict_types=1);

namespace Modules\Inventory\Enums;

/**
 * Purchase Order Status Enum
 *
 * Defines the status lifecycle of purchase orders
 */
enum POStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case RECEIVED = 'received';
    case CANCELLED = 'cancelled';

    /**
     * Get all available values
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PENDING => 'Pending',
            self::APPROVED => 'Approved',
            self::RECEIVED => 'Received',
            self::CANCELLED => 'Cancelled',
        };
    }

    /**
     * Check if PO can be edited
     */
    public function isEditable(): bool
    {
        return in_array($this, [self::DRAFT, self::PENDING]);
    }

    /**
     * Check if PO can be received
     */
    public function canReceive(): bool
    {
        return $this === self::APPROVED;
    }
}
