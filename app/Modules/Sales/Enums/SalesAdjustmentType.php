<?php

declare(strict_types=1);

namespace Modules\Sales\Enums;

enum SalesAdjustmentType: string
{
    case Discount = 'discount';
    case Tax = 'tax';
    case Freight = 'freight';
    case Charge = 'charge';
    case Insurance = 'insurance';
    case ServiceCharge = 'service_charge';
    case Duty = 'duty';
    case Levy = 'levy';
    case Withholding = 'withholding';
    case Rounding = 'rounding';
    case CreditNote = 'credit_note';
    case DebitNote = 'debit_note';
    case Custom = 'custom';
}
