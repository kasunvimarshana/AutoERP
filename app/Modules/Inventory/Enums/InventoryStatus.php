<?php

declare(strict_types=1);

namespace Modules\Inventory\Enums;

enum InventoryStatus: string
{
    case Draft = 'draft';
    case Posted = 'posted';
    case Reversed = 'reversed';
    case Cancelled = 'cancelled';
}
