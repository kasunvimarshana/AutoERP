<?php

declare(strict_types=1);

namespace Modules\Sales\Enums;

enum SalesOrderStatus: string
{
    case Draft = 'draft';
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case Closed = 'closed';
    case Cancelled = 'cancelled';
}
