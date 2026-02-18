<?php

declare(strict_types=1);

namespace Modules\Inventory\Enums;

/**
 * Location Type Enum
 */
enum LocationType: string
{
    case WAREHOUSE = 'warehouse';
    case ZONE = 'zone';
    case AISLE = 'aisle';
    case RACK = 'rack';
    case BIN = 'bin';
    case SHELF = 'shelf';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::WAREHOUSE => 'Warehouse',
            self::ZONE => 'Zone',
            self::AISLE => 'Aisle',
            self::RACK => 'Rack',
            self::BIN => 'Bin',
            self::SHELF => 'Shelf',
        };
    }
}
