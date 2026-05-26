<?php

declare(strict_types=1);

namespace Modules\Purchase\Domain\Enums;

enum InvoiceStatus: string
{
    case Draft = 'draft';
    case Approved = 'approved';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case Disputed = 'disputed';
    case Cancelled = 'cancelled';
}
