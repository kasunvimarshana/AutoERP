<?php

declare(strict_types=1);

namespace Modules\Sales\Enums;

enum SalesOrderStatus: string
{
    case Draft = 'draft';
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case PartiallyAllocated = 'partially_allocated';
    case Allocated = 'allocated';
    case PartiallyDelivered = 'partially_delivered';
    case Delivered = 'delivered';
    case PartiallyInvoiced = 'partially_invoiced';
    case Invoiced = 'invoiced';
    case PartiallyReturned = 'partially_returned';
    case Returned = 'returned';
    case Closed = 'closed';
    case Cancelled = 'cancelled';
}
