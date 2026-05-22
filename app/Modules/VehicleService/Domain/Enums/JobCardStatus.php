<?php

declare(strict_types=1);

namespace Modules\VehicleService\Domain\Enums;

enum JobCardStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case WaitingParts = 'waiting_parts';
    case Completed = 'completed';
    case Invoiced = 'invoiced';
    case Cancelled = 'cancelled';
}
