<?php

namespace Modules\Maintenance\Domain\Enums;

enum EquipmentStatus: string
{
    case ACTIVE              = 'active';
    case UNDER_MAINTENANCE   = 'under_maintenance';
    case DECOMMISSIONED      = 'decommissioned';
}
