<?php

namespace Modules\Fleet\Domain\Enums;

enum MaintenanceType: string
{
    case OilChange     = 'oil_change';
    case TireRotation  = 'tire_rotation';
    case Inspection    = 'inspection';
    case Repair        = 'repair';
    case Other         = 'other';
}
