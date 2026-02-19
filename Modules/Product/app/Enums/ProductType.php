<?php

declare(strict_types=1);

namespace Modules\Product\Enums;

/**
 * Product Type Enum
 *
 * Defines possible product types
 */
enum ProductType: string
{
    case GOODS = 'goods';
    case SERVICES = 'services';
    case DIGITAL = 'digital';
    case BUNDLE = 'bundle';
    case COMPOSITE = 'composite';

    /**
     * Get all product type values
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get product type label
     */
    public function label(): string
    {
        return match ($this) {
            self::GOODS => 'Goods',
            self::SERVICES => 'Services',
            self::DIGITAL => 'Digital',
            self::BUNDLE => 'Bundle',
            self::COMPOSITE => 'Composite',
        };
    }

    /**
     * Check if product type requires inventory tracking
     */
    public function requiresInventory(): bool
    {
        return match ($this) {
            self::GOODS, self::COMPOSITE => true,
            self::SERVICES, self::DIGITAL, self::BUNDLE => false,
        };
    }
}
