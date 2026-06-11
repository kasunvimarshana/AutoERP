<?php

declare(strict_types=1);

namespace Modules\Sales\Enums;

enum SalesAdjustmentAllocationMethod: string
{
    case Proportional = 'proportional';
    case Manual = 'manual';
    case FirstInvoice = 'first_invoice';
    case LastInvoice = 'last_invoice';
}
