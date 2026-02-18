<?php

declare(strict_types=1);

namespace Modules\Sales\Enums;

enum OrderStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case PROCESSING = 'processing';
    case SHIPPED = 'shipped';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';
    case COMPLETED = 'completed';

    /**
     * Get a human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PENDING => 'Pending',
            self::CONFIRMED => 'Confirmed',
            self::PROCESSING => 'Processing',
            self::SHIPPED => 'Shipped',
            self::DELIVERED => 'Delivered',
            self::CANCELLED => 'Cancelled',
            self::COMPLETED => 'Completed',
        };
    }

    /**
     * Check if the order can be edited.
     */
    public function canEdit(): bool
    {
        return in_array($this, [self::DRAFT, self::PENDING]);
    }

    /**
     * Check if the order can be cancelled.
     */
    public function canCancel(): bool
    {
        return in_array($this, [self::DRAFT, self::PENDING, self::CONFIRMED]);
    }
}
