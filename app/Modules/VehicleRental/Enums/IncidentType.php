<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

/** Nature of reported rental evidence, never an automatic charging entitlement. */
enum IncidentType: string
{
    case Fuel = 'fuel';
    case Toll = 'toll';
    case Parking = 'parking';
    case Repair = 'repair';
    case Maintenance = 'maintenance';
    case AccidentDamage = 'accident_damage';
    case Penalty = 'penalty';
    case Cleaning = 'cleaning';
    case Other = 'other';
}
