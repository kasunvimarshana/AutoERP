<?php

namespace Modules\ProjectManagement\Domain\Enums;

enum TaskStatus: string
{
    case TODO        = 'todo';
    case IN_PROGRESS = 'in_progress';
    case REVIEW      = 'review';
    case DONE        = 'done';
}
