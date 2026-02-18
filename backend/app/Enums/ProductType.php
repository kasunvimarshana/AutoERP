<?php

namespace App\Enums;

/**
 * Product Type Enum
 *
 * Defines the different types of products in the system.
 */
enum ProductType: string
{
    case INVENTORY = 'inventory';
    case SERVICE = 'service';
    case COMBO = 'combo';
    case BUNDLE = 'bundle';

    /**
     * Check if the product type is physical inventory
     */
    public function isPhysical(): bool
    {
        return $this === self::INVENTORY;
    }

    /**
     * Check if the product type requires stock tracking
     */
    public function requiresStockTracking(): bool
    {
        return in_array($this, [self::INVENTORY, self::COMBO, self::BUNDLE]);
    }

    /**
     * Get display label
     */
    public function label(): string
    {
        return match ($this) {
            self::INVENTORY => 'Inventory Item',
            self::SERVICE => 'Service',
            self::COMBO => 'Combo Product',
            self::BUNDLE => 'Bundle Product',
        };
    }

    /**
     * Get all values as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
