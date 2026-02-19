<?php

declare(strict_types=1);

namespace Modules\Appointment\Enums;

/**
 * Service Type Enum
 */
enum ServiceType: string
{
    case OIL_CHANGE = 'oil_change';
    case TIRE_ROTATION = 'tire_rotation';
    case BRAKE_SERVICE = 'brake_service';
    case ENGINE_DIAGNOSTIC = 'engine_diagnostic';
    case GENERAL_INSPECTION = 'general_inspection';
    case TRANSMISSION = 'transmission';
    case ELECTRICAL = 'electrical';
    case DETAILING = 'detailing';
    case OTHER = 'other';

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
            self::OIL_CHANGE => 'Oil Change',
            self::TIRE_ROTATION => 'Tire Rotation',
            self::BRAKE_SERVICE => 'Brake Service',
            self::ENGINE_DIAGNOSTIC => 'Engine Diagnostic',
            self::GENERAL_INSPECTION => 'General Inspection',
            self::TRANSMISSION => 'Transmission',
            self::ELECTRICAL => 'Electrical',
            self::DETAILING => 'Detailing',
            self::OTHER => 'Other',
        };
    }
}
