<?php

declare(strict_types=1);

namespace Modules\Pricing\Enums;

/**
 * Discount Type Enum
 *
 * Defines the type of discount
 */
enum DiscountType: string
{
    case PERCENTAGE = 'percentage';
    case FIXED_AMOUNT = 'fixed_amount';
    case BUY_X_GET_Y = 'buy_x_get_y';
    case BUNDLE = 'bundle';

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
            self::PERCENTAGE => 'Percentage Discount',
            self::FIXED_AMOUNT => 'Fixed Amount Discount',
            self::BUY_X_GET_Y => 'Buy X Get Y',
            self::BUNDLE => 'Bundle Discount',
        };
    }

    /**
     * Check if discount type requires percentage value
     */
    public function isPercentage(): bool
    {
        return $this === self::PERCENTAGE;
    }

    /**
     * Check if discount type requires fixed amount
     */
    public function isFixedAmount(): bool
    {
        return $this === self::FIXED_AMOUNT;
    }
}
