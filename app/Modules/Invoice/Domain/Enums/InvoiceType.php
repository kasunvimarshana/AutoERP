<?php

declare(strict_types=1);

namespace Modules\Invoice\Domain\Enums;

enum InvoiceType: string
{
    case Sales = 'sales';
    case Purchase = 'purchase';
    case Service = 'service';
    case Rental = 'rental';
    case Manual = 'manual';
    case DebitNote = 'debit_note';
    case CreditNote = 'credit_note';
}
