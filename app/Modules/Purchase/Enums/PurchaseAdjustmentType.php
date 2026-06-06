<?php

declare(strict_types=1);

namespace Modules\Purchase\Enums;

enum PurchaseAdjustmentType: string
{
    case Discount = 'discount';
    case Tax = 'tax';
    case Freight = 'freight';
    case Charge = 'charge';
    case Insurance = 'insurance';
    case ServiceCharge = 'service_charge';
    case Duty = 'duty';
    case Levy = 'levy';
    case CreditNote = 'credit_note';
    case DebitNote = 'debit_note';
    case Withholding = 'withholding';
    case Rounding = 'rounding';
    case Custom = 'custom';
    case Other = 'other';
}
