<?php

namespace Modules\Manufacturing\Domain\Enums;

enum WorkOrderStatus: string
{
    case DRAFT       = 'draft';
    case CONFIRMED   = 'confirmed';
    case IN_PROGRESS = 'in_progress';
    case DONE        = 'done';
    case CANCELLED   = 'cancelled';
}
