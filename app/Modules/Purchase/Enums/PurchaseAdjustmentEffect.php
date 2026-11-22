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

enum PurchaseAdjustmentEffect: string
{
    case Increase = 'increase';
    case Decrease = 'decrease';
}
