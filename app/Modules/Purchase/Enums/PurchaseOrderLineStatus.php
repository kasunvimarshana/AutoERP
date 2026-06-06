<?php

declare(strict_types=1);

namespace Modules\Purchase\Enums;

enum PurchaseOrderLineStatus: string
{
    case Open = 'open';
    case PartiallyReceived = 'partially_received';
    case Received = 'received';
    case PartiallyInvoiced = 'partially_invoiced';
    case Invoiced = 'invoiced';
    case Closed = 'closed';
    case Cancelled = 'cancelled';
}
