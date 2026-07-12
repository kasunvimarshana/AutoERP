<?php

declare(strict_types=1);

namespace Modules\Invoice\Enums;

enum InvoiceBalanceStatus: string
{
    case Unpaid = 'unpaid';
    case Partial = 'partial';
    case Paid = 'paid';
    case Overpaid = 'overpaid';
    case Reversed = 'reversed';
    case Cancelled = 'cancelled';
    case Void = 'void';
}
