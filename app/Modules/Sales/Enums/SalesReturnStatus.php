<?php

declare(strict_types=1);

namespace Modules\Sales\Enums;

enum SalesReturnStatus: string
{
    case Draft = 'draft';
    case Approved = 'approved';
    case Posted = 'posted';
    case Cancelled = 'cancelled';
    case Reversed = 'reversed';
}
