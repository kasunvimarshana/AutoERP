<?php

namespace Modules\QualityControl\Domain\Enums;

enum AlertStatus: string
{
    case Open     = 'open';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Closed   = 'closed';
}
