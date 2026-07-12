<?php

declare(strict_types=1);

namespace Modules\Invoice\Enums;

enum InvoicePostingPlanStatus: string
{
    case Prepared = 'prepared';
    case Posted = 'posted';
    case Reversed = 'reversed';
}
