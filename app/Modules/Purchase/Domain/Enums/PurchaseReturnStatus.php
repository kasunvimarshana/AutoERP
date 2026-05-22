<?php

declare(strict_types=1);

namespace Modules\Purchase\Domain\Enums;

enum PurchaseReturnStatus: string
{
    case Draft = 'draft';
    case Approved = 'approved';
    case Closed = 'closed';
    case Cancelled = 'cancelled';
}
