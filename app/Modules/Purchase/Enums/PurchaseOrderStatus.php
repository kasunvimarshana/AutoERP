<?php

declare(strict_types=1);

namespace Modules\Purchase\Enums;

enum PurchaseOrderStatus: string
{
    case Draft = 'draft';
    case Approved = 'approved';
    case PartiallyReceived = 'partially_received';
    case Received = 'received';
    case PartiallyInvoiced = 'partially_invoiced';
    case Invoiced = 'invoiced';
    case Closed = 'closed';
    case Cancelled = 'cancelled';
}
