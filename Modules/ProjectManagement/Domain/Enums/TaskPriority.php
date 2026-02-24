<?php

namespace Modules\ProjectManagement\Domain\Enums;

enum TaskPriority: string
{
    case LOW      = 'low';
    case MEDIUM   = 'medium';
    case HIGH     = 'high';
    case CRITICAL = 'critical';
}
