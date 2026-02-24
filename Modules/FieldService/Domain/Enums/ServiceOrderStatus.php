<?php

namespace Modules\FieldService\Domain\Enums;

enum ServiceOrderStatus: string
{
    case New        = 'new';
    case Assigned   = 'assigned';
    case InProgress = 'in_progress';
    case Done       = 'done';
    case Invoiced   = 'invoiced';
    case Cancelled  = 'cancelled';
}
