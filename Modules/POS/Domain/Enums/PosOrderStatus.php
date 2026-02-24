<?php

namespace Modules\POS\Domain\Enums;

enum PosOrderStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';
}
