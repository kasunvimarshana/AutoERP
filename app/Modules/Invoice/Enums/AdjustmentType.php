<?php

declare(strict_types=1);

namespace Modules\Invoice\Enums;

enum AdjustmentType: string
{
    case Discount = 'discount';
    case Tax = 'tax';
    case Freight = 'freight';
    case Charge = 'charge';
    case CreditNote = 'credit_note';
    case DebitNote = 'debit_note';
    case Withholding = 'withholding';
    case Rounding = 'rounding';
    case Other = 'other';
}
