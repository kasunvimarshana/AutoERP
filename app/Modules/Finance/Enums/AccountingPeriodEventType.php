<?php

declare(strict_types=1);

namespace Modules\Finance\Enums;

enum AccountingPeriodEventType: string
{
    case Created = 'created';
    case Closed = 'closed';
    case Reopened = 'reopened';
}
