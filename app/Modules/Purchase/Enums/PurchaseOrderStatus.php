<?php

declare(strict_types=1);

namespace Modules\Purchase\Enums;

enum PurchaseOrderStatus: string
{
    case Draft = 'draft';
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case PartiallyReceived = 'partially_received';
    case Received = 'received';
    case PartiallyInvoiced = 'partially_invoiced';
    case Invoiced = 'invoiced';
    case PartiallyReturned = 'partially_returned';
    case Returned = 'returned';
    case Closed = 'closed';
    case Cancelled = 'cancelled';
}
