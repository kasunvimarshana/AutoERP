<?php

declare(strict_types=1);

namespace Modules\Purchase\Enums;

/**
 * Vendor Status Enum
 *
 * Represents the operational status of a vendor/supplier.
 */
enum VendorStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case BLOCKED = 'blocked';
    case PENDING = 'pending';

    /**
     * Get human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::BLOCKED => 'Blocked',
            self::PENDING => 'Pending Approval',
        };
    }

    /**
     * Get color class for UI representation.
     */
    public function color(): string
    {
        return match ($this) {
            self::ACTIVE => 'green',
            self::INACTIVE => 'gray',
            self::BLOCKED => 'red',
            self::PENDING => 'yellow',
        };
    }

    /**
     * Check if vendor can receive new orders.
     */
    public function canReceiveOrders(): bool
    {
        return $this === self::ACTIVE;
    }
}
