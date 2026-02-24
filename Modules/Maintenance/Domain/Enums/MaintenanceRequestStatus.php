<?php

namespace Modules\Maintenance\Domain\Enums;

enum MaintenanceRequestStatus: string
{
    case NEW         = 'new';
    case IN_PROGRESS = 'in_progress';
    case DONE        = 'done';
    case CANCELLED   = 'cancelled';
}
