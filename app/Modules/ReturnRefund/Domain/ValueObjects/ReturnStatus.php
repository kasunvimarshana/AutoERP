<?php

declare(strict_types=1);

namespace Modules\ReturnRefund\Domain\ValueObjects;

enum ReturnStatus: string
{
    case Pending       = 'pending';
    case Inspected     = 'inspected';
    case Refunded      = 'refunded';
    case PartialRefund = 'partial_refund';
    case NoRefund      = 'no_refund';
}
