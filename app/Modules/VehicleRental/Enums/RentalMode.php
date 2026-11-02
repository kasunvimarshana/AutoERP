<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalMode: string
{
    case WithDriver = 'with_driver';
    case SelfDrive = 'self_drive';
    case VehicleOnly = 'vehicle_only';
}
