<?php

declare(strict_types=1);

namespace Modules\Sales\Enums;

enum SalesAdjustmentCalculationType: string
{
    case Fixed = 'fixed';
    case Percentage = 'percentage';
}
