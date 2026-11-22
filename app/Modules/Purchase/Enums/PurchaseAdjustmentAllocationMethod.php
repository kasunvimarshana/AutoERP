<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Purchase\Enums;

enum PurchaseAdjustmentAllocationMethod: string
{
    case Proportional = 'proportional';
    case Manual = 'manual';
    case FirstInvoice = 'first_invoice';
    case LastInvoice = 'last_invoice';
}
