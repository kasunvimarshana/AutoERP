<?php

namespace Modules\Accounting\Domain\Enums;

enum InvoiceStatus: string
{
    case Draft     = 'draft';
    case Sent      = 'sent';
    case Paid      = 'paid';
    case Cancelled = 'cancelled';
    case Overdue   = 'overdue';
}
