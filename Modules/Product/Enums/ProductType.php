<?php

declare(strict_types=1);

namespace Modules\Product\Enums;

/**
 * ProductType Enum
 *
 * Defines the types of products in the system
 */
enum ProductType: string
{
    case GOOD = 'good';
    case SERVICE = 'service';
    case BUNDLE = 'bundle';
    case COMPOSITE = 'composite';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::GOOD => 'Physical Good',
            self::SERVICE => 'Service',
            self::BUNDLE => 'Bundle',
            self::COMPOSITE => 'Composite Product',
        };
    }

    /**
     * Check if this product type can have inventory
     */
    public function hasInventory(): bool
    {
        return match ($this) {
            self::GOOD, self::COMPOSITE => true,
            self::SERVICE, self::BUNDLE => false,
        };
    }
}
