<?php

declare(strict_types=1);

namespace Modules\Purchase\Enums;

enum PurchaseAdjustmentEffect: string
{
    case Increase = 'increase';
    case Decrease = 'decrease';
}
