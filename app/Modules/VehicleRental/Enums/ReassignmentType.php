<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\VehicleRental\Enums;

enum ReassignmentType: string
{
    case VehicleReplacement = 'vehicle_replacement';
    case DriverReassignment = 'driver_reassignment';
    case Combined = 'combined';
}
