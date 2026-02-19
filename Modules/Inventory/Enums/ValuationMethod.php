<?php

declare(strict_types=1);

namespace Modules\Inventory\Enums;

/**
 * ValuationMethod Enum
 *
 * Defines inventory valuation methods.
 */
enum ValuationMethod: string
{
    case FIFO = 'fifo';
    case LIFO = 'lifo';
    case WEIGHTED_AVERAGE = 'weighted_average';
    case STANDARD_COST = 'standard_cost';

    /**
     * Get human-readable label for the enum value.
     */
    public function label(): string
    {
        return match ($this) {
            self::FIFO => 'First In First Out',
            self::LIFO => 'Last In First Out',
            self::WEIGHTED_AVERAGE => 'Weighted Average',
            self::STANDARD_COST => 'Standard Cost',
        };
    }

    /**
     * Get description for the enum value.
     */
    public function description(): string
    {
        return match ($this) {
            self::FIFO => 'First items received are first items issued',
            self::LIFO => 'Last items received are first items issued',
            self::WEIGHTED_AVERAGE => 'Average cost weighted by quantity',
            self::STANDARD_COST => 'Fixed standard cost per unit',
        };
    }
}
