<?php

declare(strict_types=1);

namespace Modules\Inventory\Enums;

enum CostAdjustmentStatus: string
{
    case Draft = 'draft';
    case Posted = 'posted';
    case Cancelled = 'cancelled';
}
