<?php

declare(strict_types=1);

namespace Modules\Workflow\Domain\Enums;

enum WorkflowInstanceStatus: string
{
    case Active = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case OnHold = 'on_hold';
}
