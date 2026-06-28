<?php

declare(strict_types=1);

namespace Modules\Idempotency\Enums;

enum IdempotencyStatus: string
{
    case InProgress = 'in_progress';
    case Completed = 'completed';
}
