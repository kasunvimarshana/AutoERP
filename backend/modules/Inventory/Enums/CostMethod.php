<?php

declare(strict_types=1);

namespace Modules\Inventory\Enums;

/**
 * Inventory Costing Method Enum
 *
 * Defines the methods for calculating inventory costs.
 */
enum CostMethod: string
{
    /**
     * First In, First Out - oldest stock consumed first
     */
    case FIFO = 'fifo';

    /**
     * Last In, First Out - newest stock consumed first
     */
    case LIFO = 'lifo';

    /**
     * Weighted Average Cost - average cost of all units
     */
    case WAC = 'wac';

    /**
     * Standard Cost - predetermined fixed cost
     */
    case STANDARD = 'standard';

    /**
     * Specific Identification - actual cost of specific units
     */
    case SPECIFIC = 'specific';

    /**
     * Get all available cost methods
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get display label for the cost method
     */
    public function label(): string
    {
        return match ($this) {
            self::FIFO => 'First In, First Out (FIFO)',
            self::LIFO => 'Last In, First Out (LIFO)',
            self::WAC => 'Weighted Average Cost (WAC)',
            self::STANDARD => 'Standard Cost',
            self::SPECIFIC => 'Specific Identification',
        };
    }

    /**
     * Get description of the cost method
     */
    public function description(): string
    {
        return match ($this) {
            self::FIFO => 'Cost of goods sold based on oldest inventory first',
            self::LIFO => 'Cost of goods sold based on newest inventory first',
            self::WAC => 'Cost of goods sold based on weighted average of all inventory',
            self::STANDARD => 'Cost of goods sold based on predetermined standard cost',
            self::SPECIFIC => 'Cost of goods sold based on actual cost of specific items',
        };
    }
}
