<?php

declare(strict_types=1);

namespace Modules\Invoice\Domain\Enums;

enum InvoiceAdjustmentType: string
{
    case Discount = 'discount';
    case Tax = 'tax';
    case Charge = 'charge';
    case Rounding = 'rounding';
    case Withholding = 'withholding';
    case Other = 'other';
}
