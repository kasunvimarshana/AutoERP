<?php

namespace Modules\Accounting\Domain\Enums;

enum AccountingPeriodStatus: string
{
    case Draft  = 'draft';
    case Open   = 'open';
    case Closed = 'closed';
    case Locked = 'locked';
}
