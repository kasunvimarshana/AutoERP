<?php

namespace Modules\Leave\Domain\Enums;

enum LeaveAllocationStatus: string
{
    case Draft    = 'draft';
    case Approved = 'approved';
    case Expired  = 'expired';
}
