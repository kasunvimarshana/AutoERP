<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Payment\Enums;

enum PaymentAllocationMethod: string
{
    case Manual = 'manual';
    case SpecificInvoice = 'specific_invoice';
    case Fifo = 'fifo';
}
