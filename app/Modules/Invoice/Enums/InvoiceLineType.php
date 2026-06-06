<?php

declare(strict_types=1);

namespace Modules\Invoice\Enums;

enum InvoiceLineType: string
{
    case Item = 'item';
    case Service = 'service';
    case Labour = 'labour';
    case Charge = 'charge';
    case Discount = 'discount';
    case Tax = 'tax';
    case Rounding = 'rounding';
    case Manual = 'manual';
}
