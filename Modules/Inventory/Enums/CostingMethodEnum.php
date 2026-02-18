<?php

declare(strict_types=1);

namespace Modules\Inventory\Enums;

/**
 * Costing Method Enum
 *
 * Inventory valuation methods.
 *
 * @package Modules\Inventory\Enums
 */
enum CostingMethodEnum: string
{
    case FIFO = 'fifo';
    case LIFO = 'lifo';
    case AVERAGE = 'average';
    case STANDARD = 'standard';
    case SPECIFIC_IDENTIFICATION = 'specific_identification';

    /**
     * Get all costing method values
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
            self::FIFO => 'First In, First Out (FIFO)',
            self::LIFO => 'Last In, First Out (LIFO)',
            self::AVERAGE => 'Weighted Average',
            self::STANDARD => 'Standard Cost',
            self::SPECIFIC_IDENTIFICATION => 'Specific Identification',
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
            self::FIFO => 'Oldest inventory costs are used first',
            self::LIFO => 'Newest inventory costs are used first',
            self::AVERAGE => 'Average cost of all inventory',
            self::STANDARD => 'Predetermined standard cost',
            self::SPECIFIC_IDENTIFICATION => 'Track individual item costs',
        };
    }

    /**
     * Check if method requires lot tracking
     *
     * @return bool
     */
    public function requiresLotTracking(): bool
    {
        return in_array($this, [
            self::FIFO,
            self::LIFO,
            self::SPECIFIC_IDENTIFICATION,
        ], true);
    }

    /**
     * Check if method allows cost updates
     *
     * @return bool
     */
    public function allowsCostUpdates(): bool
    {
        return $this !== self::STANDARD;
    }

    /**
     * Get accounting treatment
     *
     * @return string
     */
    public function accountingTreatment(): string
    {
        return match ($this) {
            self::FIFO => 'GAAP approved, matches physical flow',
            self::LIFO => 'GAAP approved in US, not allowed in IFRS',
            self::AVERAGE => 'GAAP and IFRS approved',
            self::STANDARD => 'Manufacturing environments',
            self::SPECIFIC_IDENTIFICATION => 'High-value unique items',
        };
    }
}
