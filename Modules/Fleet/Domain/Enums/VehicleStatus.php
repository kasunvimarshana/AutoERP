<?php

namespace Modules\Fleet\Domain\Enums;

enum VehicleStatus: string
{
    case Active      = 'active';
    case Maintenance = 'maintenance';
    case Retired     = 'retired';
}
