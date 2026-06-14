<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalExpenseType: string
{
    case Fuel = 'fuel';
    case Toll = 'toll';
    case Parking = 'parking';
    case Allowance = 'allowance';
    case Repair = 'repair';
    case Other = 'other';
}
