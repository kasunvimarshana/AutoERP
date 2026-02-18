<?php

declare(strict_types=1);

namespace Modules\Procurement\Enums;

/**
 * Purchase Order Status Enum
 *
 * Lifecycle statuses for purchase orders.
 *
 * @package Modules\Procurement\Enums
 */
enum PurchaseOrderStatusEnum: string
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
            self::PENDING => 'Pending Approval',
            self::APPROVED => 'Approved',
            self::SENT => 'Sent to Supplier',
            self::CONFIRMED => 'Confirmed by Supplier',
            self::PARTIALLY_RECEIVED => 'Partially Received',
            self::RECEIVED => 'Fully Received',
            self::CANCELLED => 'Cancelled',
            self::CLOSED => 'Closed',
        };
    }

    /**
     * Check if PO can be modified
     *
     * @return bool
     */
    public function isEditable(): bool
    {
        return in_array($this, [
            self::DRAFT,
            self::PENDING,
        ], true);
    }

    /**
     * Check if PO is final
     *
     * @return bool
     */
    public function isFinal(): bool
    {
        return in_array($this, [
            self::RECEIVED,
            self::CANCELLED,
            self::CLOSED,
        ], true);
    }

    /**
     * Check if goods can be received
     *
     * @return bool
     */
    public function canReceiveGoods(): bool
    {
        return in_array($this, [
            self::CONFIRMED,
            self::PARTIALLY_RECEIVED,
        ], true);
    }

    /**
     * Get next possible statuses
     *
     * @return array<PurchaseOrderStatusEnum>
     */
    public function nextStatuses(): array
    {
        return match ($this) {
            self::DRAFT => [self::PENDING, self::CANCELLED],
            self::PENDING => [self::APPROVED, self::CANCELLED],
            self::APPROVED => [self::SENT, self::CANCELLED],
            self::SENT => [self::CONFIRMED, self::CANCELLED],
            self::CONFIRMED => [self::PARTIALLY_RECEIVED, self::RECEIVED, self::CANCELLED],
            self::PARTIALLY_RECEIVED => [self::RECEIVED, self::CANCELLED],
            self::RECEIVED => [self::CLOSED],
            default => [],
        };
    }

    /**
     * Get color for UI
     *
     * @return string
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
}
