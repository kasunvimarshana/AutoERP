<?php

declare(strict_types=1);

namespace Modules\Pricing\Enums;

/**
 * Price Type Enum
 *
 * Defines the type of pricing strategy used
 */
enum PriceType: string
{
    case FLAT = 'flat';
    case PERCENTAGE = 'percentage';
    case TIERED = 'tiered';
    case RULES_BASED = 'rules_based';
    case LOCATION_BASED = 'location_based';
    case CUSTOMER_GROUP = 'customer_group';

    /**
     * Get all enum values
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
            self::FLAT => 'Flat Price',
            self::PERCENTAGE => 'Percentage Markup',
            self::TIERED => 'Tiered Pricing',
            self::RULES_BASED => 'Rules-Based Pricing',
            self::LOCATION_BASED => 'Location-Based Pricing',
            self::CUSTOMER_GROUP => 'Customer Group Pricing',
        };
    }

    /**
     * Check if price type requires base price
     */
    public function requiresBasePrice(): bool
    {
        return match ($this) {
            self::PERCENTAGE => true,
            default => false,
        };
    }

    /**
     * Check if price type supports quantity breaks
     */
    public function supportsQuantityBreaks(): bool
    {
        return $this === self::TIERED;
    }
}
