<?php

namespace Modules\Inventory\Domain\Enums;

enum CycleCountStatus: string
{
    case Draft      = 'draft';
    case InProgress = 'in_progress';
    case Posted     = 'posted';
    case Cancelled  = 'cancelled';
}
