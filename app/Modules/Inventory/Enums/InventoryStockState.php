<?php

declare(strict_types=1);

namespace Modules\Inventory\Enums;

enum InventoryStockState: string
{
    case Available = 'available';
    case Reserved = 'reserved';
    case Allocated = 'allocated';
    case Issued = 'issued';
    case InTransit = 'in_transit';
    case Returned = 'returned';
    case Damaged = 'damaged';
    case Quarantine = 'quarantine';
    case Expired = 'expired';
    case Scrapped = 'scrapped';
    case Reversed = 'reversed';
}
