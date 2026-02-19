<?php

declare(strict_types=1);

namespace Modules\Pricing\Enums;

/**
 * PricingStrategy Enum
 *
 * Defines pricing calculation strategies
 */
enum PricingStrategy: string
{
    case FLAT = 'flat';
    case PERCENTAGE = 'percentage';
    case TIERED = 'tiered';
    case VOLUME = 'volume';
    case TIME_BASED = 'time_based';
    case RULE_BASED = 'rule_based';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::FLAT => 'Flat Rate',
            self::PERCENTAGE => 'Percentage',
            self::TIERED => 'Tiered Pricing',
            self::VOLUME => 'Volume Pricing',
            self::TIME_BASED => 'Time-Based',
            self::RULE_BASED => 'Rule-Based',
        };
    }
}
