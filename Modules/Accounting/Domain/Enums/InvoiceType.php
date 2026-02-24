<?php

namespace Modules\Accounting\Domain\Enums;

enum InvoiceType: string
{
    case Invoice    = 'invoice';
    case CreditNote = 'credit_note';
}
