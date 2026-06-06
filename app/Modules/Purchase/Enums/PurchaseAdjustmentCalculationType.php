<?php

declare(strict_types=1);

namespace Modules\Purchase\Enums;

enum PurchaseAdjustmentCalculationType: string
{
    case Fixed = 'fixed';
    case Percentage = 'percentage';
}
