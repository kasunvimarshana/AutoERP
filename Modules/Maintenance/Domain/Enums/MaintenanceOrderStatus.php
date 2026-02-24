<?php

namespace Modules\Maintenance\Domain\Enums;

enum MaintenanceOrderStatus: string
{
    case DRAFT       = 'draft';
    case CONFIRMED   = 'confirmed';
    case IN_PROGRESS = 'in_progress';
    case DONE        = 'done';
    case CANCELLED   = 'cancelled';
}
