<?php

declare(strict_types=1);

namespace Modules\ServiceCenter\Domain\ValueObjects;

enum ServiceJobStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
