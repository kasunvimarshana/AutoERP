<?php

declare(strict_types=1);

namespace Modules\Workflow\Domain\Enums;

enum WorkflowDefinitionStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Archived = 'archived';
}
