<?php

declare(strict_types=1);

namespace Modules\Finance\Enums;

enum FiscalPeriodStatus: string
{
    case Open = 'open';
    case Closed = 'closed';
    case Locked = 'locked';
    case YearClosed = 'year_closed';
}
