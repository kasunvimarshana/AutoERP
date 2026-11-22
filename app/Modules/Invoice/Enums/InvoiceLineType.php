<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

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
