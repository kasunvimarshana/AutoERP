<?php

declare(strict_types=1);

namespace Modules\Sales\Enums;

enum SalesDeliveryStatus: string
{
    case Draft = 'draft';
    case Posted = 'posted';
    case PartiallyReturned = 'partially_returned';
    case Returned = 'returned';
    case PartiallyInvoiced = 'partially_invoiced';
    case Invoiced = 'invoiced';
    case Cancelled = 'cancelled';
    case Reversed = 'reversed';
}
