<?php

declare(strict_types=1);

namespace Modules\Wms\Domain\Enums;

enum CycleCountStatus: string
{
    case Draft = 'draft';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
