<?php

declare(strict_types=1);

namespace Modules\Invoice\Enums;

enum AllocationMethod: string
{
    case Proportional = 'proportional';
    case Manual = 'manual';
    case FirstInvoice = 'first_invoice';
    case LastInvoice = 'last_invoice';
}
