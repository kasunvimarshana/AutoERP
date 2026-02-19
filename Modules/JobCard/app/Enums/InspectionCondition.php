<?php

declare(strict_types=1);

namespace Modules\JobCard\Enums;

/**
 * Inspection Condition Enum
 *
 * Defines the condition states for inspection items
 */
enum InspectionCondition: string
{
    case GOOD = 'good';
    case FAIR = 'fair';
    case POOR = 'poor';
    case NEEDS_REPLACEMENT = 'needs_replacement';
    case NOT_APPLICABLE = 'not_applicable';

    /**
     * Get all condition values
     *
     * @return array<int, string>
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
            self::GOOD => 'Good',
            self::FAIR => 'Fair',
            self::POOR => 'Poor',
            self::NEEDS_REPLACEMENT => 'Needs Replacement',
            self::NOT_APPLICABLE => 'Not Applicable',
        };
    }
}
