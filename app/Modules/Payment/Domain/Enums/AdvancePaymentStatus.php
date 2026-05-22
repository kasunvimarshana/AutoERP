<?php

declare(strict_types=1);

namespace Modules\Payment\Domain\Enums;

enum AdvancePaymentStatus: string
{
    case Open = 'open';
    case PartiallyApplied = 'partially_applied';
    case FullyApplied = 'fully_applied';
    case Refunded = 'refunded';
}
