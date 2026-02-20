<?php

namespace App\Enums;

enum WorkflowInstanceStatus: string
{
    case Active = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
