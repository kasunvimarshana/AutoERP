<?php

declare(strict_types=1);

namespace Modules\Payment\Enums;

enum PaymentStatus: string
{
    case Draft = 'draft';
    case Approved = 'approved';
    case Posted = 'posted';
    case PartiallyAllocated = 'partially_allocated';
    case Allocated = 'allocated';
    case Void = 'void';
    case Reversed = 'reversed';
    case Cancelled = 'cancelled';
}
