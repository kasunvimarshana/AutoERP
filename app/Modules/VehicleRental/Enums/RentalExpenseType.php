<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalExpenseType: string
{
    case Fuel = 'fuel';
    case Toll = 'toll';
    case Parking = 'parking';
    case Repair = 'repair';
    case Service = 'service';
    case Allowance = 'allowance';
    case Licence = 'licence';
    case Insurance = 'insurance';
    case Damage = 'damage';
    case Other = 'other';
}
