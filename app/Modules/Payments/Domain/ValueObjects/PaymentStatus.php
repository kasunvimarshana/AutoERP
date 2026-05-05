<?php

declare(strict_types=1);

namespace Modules\Payments\Domain\ValueObjects;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Failed = 'failed';
    case Refunded = 'refunded';
    case Cancelled = 'cancelled';
}
