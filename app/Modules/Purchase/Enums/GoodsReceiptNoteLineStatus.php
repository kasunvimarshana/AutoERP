<?php

declare(strict_types=1);

namespace Modules\Purchase\Enums;

enum GoodsReceiptNoteLineStatus: string
{
    case Open = 'open';
    case Posted = 'posted';
    case PartiallyReturned = 'partially_returned';
    case Returned = 'returned';
    case PartiallyInvoiced = 'partially_invoiced';
    case Invoiced = 'invoiced';
    case Cancelled = 'cancelled';
}
