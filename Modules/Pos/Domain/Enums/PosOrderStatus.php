<?php

declare(strict_types=1);

namespace Modules\Pos\Domain\Enums;

enum PosOrderStatus: string
{
    case Draft = 'draft';
    case Paid = 'paid';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';
    case PartialRefund = 'partial_refund';
}
