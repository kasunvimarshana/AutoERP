<?php

declare(strict_types=1);

namespace Modules\Inventory\Enums;

enum StockCountStatus: string
{
    case Draft = 'draft';
    case Approved = 'approved';
    case Posted = 'posted';
    case Cancelled = 'cancelled';
}
