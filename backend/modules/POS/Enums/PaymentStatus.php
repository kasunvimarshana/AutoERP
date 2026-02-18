<?php

declare(strict_types=1);

namespace Modules\POS\Enums;

enum PaymentStatus: string
{
    case PAID = 'paid';
    case PARTIAL = 'partial';
    case DUE = 'due';
    case PENDING = 'pending';
}
