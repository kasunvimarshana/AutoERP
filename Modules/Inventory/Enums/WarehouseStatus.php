<?php

declare(strict_types=1);

namespace Modules\Inventory\Enums;

/**
 * WarehouseStatus Enum
 *
 * Defines status values for warehouses.
 */
enum WarehouseStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case MAINTENANCE = 'maintenance';
    case CLOSED = 'closed';

    /**
     * Get human-readable label for the enum value.
     */
    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::MAINTENANCE => 'Under Maintenance',
            self::CLOSED => 'Closed',
        };
    }

    /**
     * Check if warehouse can accept stock.
     */
    public function canAcceptStock(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Check if warehouse can issue stock.
     */
    public function canIssueStock(): bool
    {
        return $this === self::ACTIVE;
    }
}
