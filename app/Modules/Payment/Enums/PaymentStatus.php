<?php

declare(strict_types=1);

namespace Modules\Payment\Enums;

enum PaymentStatus: string
{
    case Draft = 'draft';
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case Posted = 'posted';
    case PartiallyAllocated = 'partially_allocated';
    case FullyAllocated = 'fully_allocated';
    case Allocated = 'allocated';
    case Refunded = 'refunded';
    case Void = 'void';
    case Reversed = 'reversed';
    case Cancelled = 'cancelled';
}
