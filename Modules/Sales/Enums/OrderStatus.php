<?php

declare(strict_types=1);

namespace Modules\Sales\Enums;

/**
 * Order Status Enum
 *
 * Represents the lifecycle status of a sales order.
 */
enum OrderStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case ON_HOLD = 'on_hold';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PENDING => 'Pending',
            self::CONFIRMED => 'Confirmed',
            self::PROCESSING => 'Processing',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
            self::ON_HOLD => 'On Hold',
        };
    }

    /**
     * Check if order can be modified.
     */
    public function canModify(): bool
    {
        return $this === self::DRAFT;
    }

    /**
     * Check if order can be confirmed.
     */
    public function canConfirm(): bool
    {
        return in_array($this, [self::DRAFT, self::PENDING]);
    }

    /**
     * Check if order can be cancelled.
     */
    public function canCancel(): bool
    {
        return ! in_array($this, [self::COMPLETED, self::CANCELLED]);
    }

    /**
     * Check if order can be completed.
     */
    public function canComplete(): bool
    {
        return in_array($this, [self::CONFIRMED, self::PROCESSING]);
    }

    /**
     * Check if order is in a final state.
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::COMPLETED, self::CANCELLED]);
    }

    /**
     * Check if order is active.
     */
    public function isActive(): bool
    {
        return ! $this->isFinal() && $this !== self::ON_HOLD;
    }
}
