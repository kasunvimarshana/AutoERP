<?php

declare(strict_types=1);

namespace Modules\Purchase\Enums;

/**
 * Purchase Order Status Enum
 *
 * Represents the lifecycle status of a purchase order.
 */
enum PurchaseOrderStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case SENT = 'sent';
    case CONFIRMED = 'confirmed';
    case PARTIALLY_RECEIVED = 'partially_received';
    case RECEIVED = 'received';
    case CANCELLED = 'cancelled';
    case CLOSED = 'closed';

    /**
     * Get human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PENDING => 'Pending Approval',
            self::APPROVED => 'Approved',
            self::SENT => 'Sent to Vendor',
            self::CONFIRMED => 'Confirmed by Vendor',
            self::PARTIALLY_RECEIVED => 'Partially Received',
            self::RECEIVED => 'Fully Received',
            self::CANCELLED => 'Cancelled',
            self::CLOSED => 'Closed',
        };
    }

    /**
     * Get color class for UI representation.
     */
    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::PENDING => 'yellow',
            self::APPROVED => 'blue',
            self::SENT => 'indigo',
            self::CONFIRMED => 'purple',
            self::PARTIALLY_RECEIVED => 'orange',
            self::RECEIVED => 'green',
            self::CANCELLED => 'red',
            self::CLOSED => 'gray',
        };
    }

    /**
     * Check if order can be edited.
     */
    public function isEditable(): bool
    {
        return in_array($this, [self::DRAFT, self::PENDING], true);
    }

    /**
     * Check if order can be cancelled.
     */
    public function isCancellable(): bool
    {
        return ! in_array($this, [self::RECEIVED, self::CANCELLED, self::CLOSED], true);
    }

    /**
     * Check if order can receive goods.
     */
    public function canReceiveGoods(): bool
    {
        return in_array($this, [self::CONFIRMED, self::PARTIALLY_RECEIVED], true);
    }
}
