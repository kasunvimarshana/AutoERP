<?php

declare(strict_types=1);

namespace Modules\Sales\Enums;

enum SalesAdjustmentEffect: string
{
    case Increase = 'increase';
    case Decrease = 'decrease';
}
