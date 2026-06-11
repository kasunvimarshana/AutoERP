<?php

declare(strict_types=1);

namespace Modules\Sales\Enums;

enum SalesAdjustmentCalculationBase: string
{
    case Subtotal = 'subtotal';
    case SubtotalAfterLineDiscount = 'subtotal_after_line_discount';
    case SubtotalAfterLineAdjustments = 'subtotal_after_line_adjustments';
}
