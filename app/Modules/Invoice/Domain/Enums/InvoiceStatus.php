<?php

declare(strict_types=1);

namespace Modules\Invoice\Domain\Enums;

enum InvoiceStatus: string
{
    case Draft = 'draft';
    case Approved = 'approved';
    case PartialPaid = 'partial_paid';
    case Paid = 'paid';
    case Disputed = 'disputed';
    case Cancelled = 'cancelled';
}
