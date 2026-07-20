<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalAssignmentStatus: string
{
    case Planned = 'planned';
    case Active = 'active';
    case Returned = 'returned';
    case Replaced = 'replaced';
    case Cancelled = 'cancelled';
}
