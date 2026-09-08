<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum VehicleUseAction: string
{
    case Plan = 'plan';
    case Handover = 'handover';
    case ReturnVehicle = 'return';
    case Cancel = 'cancel';
}
