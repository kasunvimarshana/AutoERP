<?php

declare(strict_types=1);

namespace Modules\Purchase\Domain\Enums;

enum PurchaseInvoiceType: string
{
    case Standard = 'standard';
    case CreditNote = 'credit_note';
    case DebitNote = 'debit_note';
}
