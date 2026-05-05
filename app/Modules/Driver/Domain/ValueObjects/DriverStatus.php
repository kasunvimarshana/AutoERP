<?php

declare(strict_types=1);

namespace Modules\Driver\Domain\ValueObjects;

enum DriverStatus: string
{
    case Available = 'available';
    case OnTrip    = 'on_trip';
    case Suspended = 'suspended';
    case OffDuty   = 'off_duty';
}
