<?php

declare(strict_types=1);

namespace Modules\Purchase\Enums;

enum PurchaseOrderLineStatus: string
{
    case Open = 'open';
    case Closed = 'closed';
    case Cancelled = 'cancelled';
}
