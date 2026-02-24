<?php

namespace Modules\Leave\Domain\Enums;

enum LeaveStatus: string
{
    case Draft    = 'draft';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
}
