<?php

declare(strict_types=1);

namespace Modules\Purchase\Enums;

enum PurchaseOrderStatus: string
{
    case Draft = 'draft';
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case Closed = 'closed';
    case Cancelled = 'cancelled';
}
