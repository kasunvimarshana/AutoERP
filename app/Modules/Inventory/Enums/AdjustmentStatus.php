<?php

declare(strict_types=1);

namespace Modules\Inventory\Enums;

enum AdjustmentStatus: string
{
    case Draft = 'draft';
    case Approved = 'approved';
    case Posted = 'posted';
    case Reversed = 'reversed';
    case Cancelled = 'cancelled';
}
