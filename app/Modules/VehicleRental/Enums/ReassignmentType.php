<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum ReassignmentType: string
{
    case VehicleReplacement = 'vehicle_replacement';
    case DriverReassignment = 'driver_reassignment';
    case Combined = 'combined';
}
