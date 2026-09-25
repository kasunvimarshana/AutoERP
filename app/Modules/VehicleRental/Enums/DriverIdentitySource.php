<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum DriverIdentitySource: string
{
    case Employee = 'employee';
    case External = 'external';
}
