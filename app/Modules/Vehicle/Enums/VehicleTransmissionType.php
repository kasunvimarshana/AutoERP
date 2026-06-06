<?php

declare(strict_types=1);

namespace Modules\Vehicle\Enums;

enum VehicleTransmissionType: string
{
    case Manual = 'manual';
    case Automatic = 'automatic';
    case SemiAutomatic = 'semi_automatic';
    case CVT = 'cvt';
    case Other = 'other';
}
