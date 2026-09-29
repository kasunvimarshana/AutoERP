<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum AirConditioningMode: string
{
    case NonAc = 'non_ac';
    case Front = 'front';
    case Dual = 'dual';
}
