<?php

declare(strict_types=1);

namespace Modules\Invoicing\Domain\ValueObjects;

enum InvoiceStatus: string
{
    case Draft = 'draft';
    case Issued = 'issued';
    case Paid = 'paid';
    case Cancelled = 'cancelled';
    case Overdue = 'overdue';
}
