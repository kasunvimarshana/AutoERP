<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum DriverMode: string
{
    case SelfDrive = 'self_drive';
    case WithDriver = 'with_driver';
}
