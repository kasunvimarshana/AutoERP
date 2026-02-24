<?php

namespace Modules\Maintenance\Domain\Enums;

enum MaintenanceOrderType: string
{
    case PREVENTIVE = 'preventive';
    case CORRECTIVE = 'corrective';
}
