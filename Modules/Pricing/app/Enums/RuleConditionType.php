<?php

declare(strict_types=1);

namespace Modules\Pricing\Enums;

/**
 * Rule Condition Type Enum
 *
 * Defines the type of condition for price/discount rules
 */
enum RuleConditionType: string
{
    case QUANTITY = 'quantity';
    case SUBTOTAL = 'subtotal';
    case CUSTOMER_GROUP = 'customer_group';
    case LOCATION = 'location';
    case PRODUCT_CATEGORY = 'product_category';
    case DATE_RANGE = 'date_range';
    case DAY_OF_WEEK = 'day_of_week';
    case TIME_OF_DAY = 'time_of_day';

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
            self::QUANTITY => 'Quantity',
            self::SUBTOTAL => 'Subtotal Amount',
            self::CUSTOMER_GROUP => 'Customer Group',
            self::LOCATION => 'Location',
            self::PRODUCT_CATEGORY => 'Product Category',
            self::DATE_RANGE => 'Date Range',
            self::DAY_OF_WEEK => 'Day of Week',
            self::TIME_OF_DAY => 'Time of Day',
        };
    }

    /**
     * Check if condition requires numeric comparison
     */
    public function isNumeric(): bool
    {
        return match ($this) {
            self::QUANTITY, self::SUBTOTAL => true,
            default => false,
        };
    }
}
