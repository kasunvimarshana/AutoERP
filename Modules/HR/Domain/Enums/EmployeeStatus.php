<?php

namespace Modules\HR\Domain\Enums;

enum EmployeeStatus: string
{
    case ACTIVE     = 'active';
    case INACTIVE   = 'inactive';
    case TERMINATED = 'terminated';
}
