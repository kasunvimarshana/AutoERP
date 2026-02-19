<?php

declare(strict_types=1);

namespace Modules\Product\Enums;

/**
 * Product Status Enum
 *
 * Defines possible status values for products
 */
enum ProductStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case DISCONTINUED = 'discontinued';
    case OUT_OF_STOCK = 'out_of_stock';

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
     * Check if status is active
     */
    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Check if product can be sold
     */
    public function canBeSold(): bool
    {
        return match ($this) {
            self::ACTIVE => true,
            self::INACTIVE, self::DISCONTINUED, self::OUT_OF_STOCK => false,
        };
    }

    /**
     * Get status label
     */
    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::DISCONTINUED => 'Discontinued',
            self::OUT_OF_STOCK => 'Out of Stock',
        };
    }
}
