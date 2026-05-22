<?php

declare(strict_types=1);

namespace Modules\Sales\Domain\Enums;

enum SalesOrderStatus: string
{
    case Draft = 'draft';
    case Confirmed = 'confirmed';
    case PartiallyDelivered = 'partially_delivered';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
}
