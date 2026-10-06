<?php

declare(strict_types=1);

namespace Modules\Payment\Enums;

enum PaymentAllocationMethod: string
{
    case Manual = 'manual';
    case SpecificInvoice = 'specific_invoice';
    case Fifo = 'fifo';
}
