<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalCalculationStatus: string
{
    case Calculated = 'calculated';
    case Cancelled = 'cancelled';
}
