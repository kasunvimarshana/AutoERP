<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalUsageEventType: string
{
    case Parking = 'parking';
    case Toll = 'toll';
    case Waiting = 'waiting';
    case Outstation = 'outstation';
    case Pass = 'pass';
    case Fuel = 'fuel';
    case Repair = 'repair';
    case Damage = 'damage';
    case Other = 'other';
}
