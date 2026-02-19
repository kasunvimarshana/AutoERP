<?php

declare(strict_types=1);

namespace Modules\Appointment\Enums;

/**
 * Bay Type Enum
 */
enum BayType: string
{
    case STANDARD = 'standard';
    case EXPRESS = 'express';
    case DIAGNOSTIC = 'diagnostic';
    case DETAILING = 'detailing';
    case HEAVY_DUTY = 'heavy_duty';

    /**
     * Get all values
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get label for display
     */
    public function label(): string
    {
        return match ($this) {
            self::STANDARD => 'Standard',
            self::EXPRESS => 'Express',
            self::DIAGNOSTIC => 'Diagnostic',
            self::DETAILING => 'Detailing',
            self::HEAVY_DUTY => 'Heavy Duty',
        };
    }
}
