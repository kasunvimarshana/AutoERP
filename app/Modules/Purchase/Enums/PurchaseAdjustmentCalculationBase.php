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

enum PurchaseAdjustmentCalculationBase: string
{
    case Subtotal = 'subtotal';
    case SubtotalAfterLineDiscount = 'subtotal_after_line_discount';
    case SubtotalAfterLineAdjustments = 'subtotal_after_line_adjustments';
}
