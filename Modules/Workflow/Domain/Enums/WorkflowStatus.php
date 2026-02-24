<?php

namespace Modules\Workflow\Domain\Enums;

enum WorkflowStatus: string
{
    case Active   = 'active';
    case Inactive = 'inactive';
}
