<?php

declare(strict_types=1);

namespace Modules\Sales\Enums;

enum SalesDeliveryStatus: string
{
    case Draft = 'draft';
    case Posted = 'posted';
    case Cancelled = 'cancelled';
    case Reversed = 'reversed';
}
