<?php

declare(strict_types=1);

namespace Modules\Payment\Enums;

enum UnappliedBalanceStatus: string
{
    case Available = 'available';
    case PartiallyApplied = 'partially_applied';
    case FullyApplied = 'fully_applied';
    case Refunded = 'refunded';
    case Cancelled = 'cancelled';
}
