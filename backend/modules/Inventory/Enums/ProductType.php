<?php

declare(strict_types=1);

namespace Modules\Inventory\Enums;

/**
 * Product Type Enum
 *
 * Defines the different types of products in the system.
 */
enum ProductType: string
{
    /**
     * Physical inventory items that are tracked in stock
     */
    case INVENTORY = 'inventory';

    /**
     * Service items (consulting, maintenance, etc.)
     */
    case SERVICE = 'service';

    /**
     * Bundle of multiple products sold together
     */
    case BUNDLE = 'bundle';

    /**
     * Composite product (manufactured from components)
     */
    case COMPOSITE = 'composite';

    /**
     * Get all available product types
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get display label for the product type
     */
    public function label(): string
    {
        return match ($this) {
            self::INVENTORY => 'Physical Product',
            self::SERVICE => 'Service',
            self::BUNDLE => 'Product Bundle',
            self::COMPOSITE => 'Composite Product',
        };
    }

    /**
     * Check if product type tracks inventory
     */
    public function tracksInventory(): bool
    {
        return match ($this) {
            self::INVENTORY, self::BUNDLE, self::COMPOSITE => true,
            self::SERVICE => false,
        };
    }
}
