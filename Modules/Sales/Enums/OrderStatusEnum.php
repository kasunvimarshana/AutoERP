<?php

declare(strict_types=1);

namespace Modules\Sales\Enums;

/**
 * Order Status Enum
 *
 * Lifecycle statuses for sales orders.
 *
 * @package Modules\Sales\Enums
 */
enum OrderStatusEnum: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case PROCESSING = 'processing';
    case SHIPPED = 'shipped';
    case DELIVERED = 'delivered';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case RETURNED = 'returned';
    case REFUNDED = 'refunded';

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
            self::PENDING => 'Pending',
            self::CONFIRMED => 'Confirmed',
            self::PROCESSING => 'Processing',
            self::SHIPPED => 'Shipped',
            self::DELIVERED => 'Delivered',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
            self::RETURNED => 'Returned',
            self::REFUNDED => 'Refunded',
        };
    }

    /**
     * Check if order can be modified
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
     * Check if order is final
     *
     * @return bool
     */
    public function isFinal(): bool
    {
        return in_array($this, [
            self::COMPLETED,
            self::CANCELLED,
            self::REFUNDED,
        ], true);
    }

    /**
     * Get next possible statuses
     *
     * @return array<OrderStatusEnum>
     */
    public function nextStatuses(): array
    {
        return match ($this) {
            self::DRAFT => [self::PENDING, self::CANCELLED],
            self::PENDING => [self::CONFIRMED, self::CANCELLED],
            self::CONFIRMED => [self::PROCESSING, self::CANCELLED],
            self::PROCESSING => [self::SHIPPED, self::CANCELLED],
            self::SHIPPED => [self::DELIVERED, self::RETURNED],
            self::DELIVERED => [self::COMPLETED, self::RETURNED],
            self::RETURNED => [self::REFUNDED],
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
            self::CONFIRMED => 'blue',
            self::PROCESSING => 'indigo',
            self::SHIPPED => 'purple',
            self::DELIVERED => 'green',
            self::COMPLETED => 'green',
            self::CANCELLED => 'red',
            self::RETURNED => 'orange',
            self::REFUNDED => 'red',
        };
    }
}
