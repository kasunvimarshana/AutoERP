<?php

declare(strict_types=1);

namespace Modules\Inventory\Enums;

/**
 * Supplier Status Enum
 *
 * Defines the status of suppliers
 */
enum SupplierStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case BLOCKED = 'blocked';

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
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::BLOCKED => 'Blocked',
        };
    }

    /**
     * Check if supplier can receive orders
     */
    public function canOrder(): bool
    {
        return $this === self::ACTIVE;
    }
}
