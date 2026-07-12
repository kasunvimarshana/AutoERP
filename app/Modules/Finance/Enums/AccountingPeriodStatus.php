<?php

declare(strict_types=1);

namespace Modules\Finance\Enums;

enum AccountingPeriodStatus: string
{
    case Open = 'open';
    case Closed = 'closed';
}
