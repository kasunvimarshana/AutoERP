<?php

declare(strict_types=1);

namespace Modules\VehicleService\Enums;

enum VehicleServiceWorkforceRole: string
{
    case Supervisor = 'supervisor';
    case Technician = 'technician';
    case Helper = 'helper';
    case Inspector = 'inspector';
    case UnderWash = 'under_wash';
    case BodyWash = 'body_wash';
    case Finishing = 'finishing';
    case Custom = 'custom';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(
            static fn (self $role): string => $role->value,
            self::cases(),
        );
    }
}
