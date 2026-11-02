<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalDriverAssignmentStatus: string
{
    case Planned = 'planned';
    case Active = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
