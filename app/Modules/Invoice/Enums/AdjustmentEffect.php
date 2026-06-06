<?php

declare(strict_types=1);

namespace Modules\Invoice\Enums;

enum AdjustmentEffect: string
{
    case Increase = 'increase';
    case Decrease = 'decrease';
}
