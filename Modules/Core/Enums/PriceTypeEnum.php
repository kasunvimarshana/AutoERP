<?php

declare(strict_types=1);

namespace Modules\Core\Enums;

/**
 * Price Type Enum
 *
 * Defines different types of pricing calculations.
 * Used for flexible, plugin-style pricing rules.
 *
 * @package Modules\Core\Enums
 */
enum PriceTypeEnum: string
{
    case FLAT = 'flat';
    case PERCENTAGE = 'percentage';
    case TIERED = 'tiered';
    case VOLUME = 'volume';
    case LOCATION_BASED = 'location_based';
    case TIME_BASED = 'time_based';
    case CUSTOMER_SPECIFIC = 'customer_specific';
    case QUANTITY_BREAK = 'quantity_break';
    case BUNDLE = 'bundle';
    case DYNAMIC = 'dynamic';

    /**
     * Get all price type values
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
            self::FLAT => 'Flat Rate',
            self::PERCENTAGE => 'Percentage',
            self::TIERED => 'Tiered Pricing',
            self::VOLUME => 'Volume Discount',
            self::LOCATION_BASED => 'Location Based',
            self::TIME_BASED => 'Time Based',
            self::CUSTOMER_SPECIFIC => 'Customer Specific',
            self::QUANTITY_BREAK => 'Quantity Break',
            self::BUNDLE => 'Bundle Pricing',
            self::DYNAMIC => 'Dynamic Pricing',
        };
    }

    /**
     * Get description
     *
     * @return string
     */
    public function description(): string
    {
        return match ($this) {
            self::FLAT => 'Fixed price regardless of quantity or other factors',
            self::PERCENTAGE => 'Price calculated as percentage of base price',
            self::TIERED => 'Different prices for different quantity tiers',
            self::VOLUME => 'Discount based on total volume purchased',
            self::LOCATION_BASED => 'Price varies by customer or warehouse location',
            self::TIME_BASED => 'Price changes based on time period (e.g., seasonal)',
            self::CUSTOMER_SPECIFIC => 'Special pricing for specific customers',
            self::QUANTITY_BREAK => 'Price breaks at specific quantity thresholds',
            self::BUNDLE => 'Special pricing when items sold together',
            self::DYNAMIC => 'Price calculated dynamically based on custom rules',
        };
    }

    /**
     * Check if this pricing type requires additional configuration
     *
     * @return bool
     */
    public function requiresConfiguration(): bool
    {
        return match ($this) {
            self::FLAT => false,
            default => true,
        };
    }

    /**
     * Check if this pricing type supports location-based pricing
     *
     * @return bool
     */
    public function supportsLocationPricing(): bool
    {
        return in_array($this, [
            self::LOCATION_BASED,
            self::DYNAMIC,
        ], true);
    }
}
