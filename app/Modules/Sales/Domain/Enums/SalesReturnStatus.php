<?php

declare(strict_types=1);

namespace Modules\Sales\Domain\Enums;

enum SalesReturnStatus: string
{
    case Draft = 'draft';
    case Approved = 'approved';
    case Closed = 'closed';
    case Cancelled = 'cancelled';
}
