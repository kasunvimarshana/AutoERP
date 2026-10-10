<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum IncidentStatus: string
{
    case Recorded = 'recorded';
    case Confirmed = 'confirmed';
    case Rejected = 'rejected';
}
