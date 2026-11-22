<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Payment\Enums;

enum PaymentStatus: string
{
    case Draft = 'draft';
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case Posted = 'posted';
    case Voided = 'voided';
    case Reversed = 'reversed';
    case Cancelled = 'cancelled';
    case PartiallyAllocated = 'partially_allocated';
    case FullyAllocated = 'fully_allocated';
    case Allocated = 'allocated';
    case Refunded = 'refunded';
    case Void = 'void';
}
