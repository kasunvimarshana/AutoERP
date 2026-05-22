<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Enums;

enum TaskStatus: string
{
    case Pending = 'PENDING';
    case InProgress = 'IN_PROGRESS';
    case Completed = 'COMPLETED';
    case Cancelled = 'CANCELLED';
}
