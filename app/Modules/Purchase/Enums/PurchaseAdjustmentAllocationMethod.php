<?php

declare(strict_types=1);

namespace Modules\Purchase\Enums;

enum PurchaseAdjustmentAllocationMethod: string
{
    case Proportional = 'proportional';
    case Manual = 'manual';
    case FirstInvoice = 'first_invoice';
    case LastInvoice = 'last_invoice';
}
