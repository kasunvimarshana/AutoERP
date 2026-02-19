<?php

declare(strict_types=1);

namespace Modules\Customer\Enums;

/**
 * Service Type Enum
 *
 * Defines possible service types for vehicle service records
 */
enum ServiceType: string
{
    case REGULAR = 'regular';
    case MAJOR = 'major';
    case REPAIR = 'repair';
    case INSPECTION = 'inspection';
    case WARRANTY = 'warranty';
    case EMERGENCY = 'emergency';

    /**
     * Get all service type values
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get service type label
     */
    public function label(): string
    {
        return match ($this) {
            self::REGULAR => 'Regular Service',
            self::MAJOR => 'Major Service',
            self::REPAIR => 'Repair',
            self::INSPECTION => 'Inspection',
            self::WARRANTY => 'Warranty Service',
            self::EMERGENCY => 'Emergency Service',
        };
    }

    /**
     * Check if service type requires immediate attention
     */
    public function isUrgent(): bool
    {
        return in_array($this, [self::EMERGENCY, self::REPAIR]);
    }
}
