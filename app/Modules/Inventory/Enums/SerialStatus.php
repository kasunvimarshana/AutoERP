<?php

declare(strict_types=1);

namespace Modules\Inventory\Enums;

enum SerialStatus: string
{
    case Available = 'available';
    case Reserved = 'reserved';
    case Issued = 'issued';
    case Sold = 'sold';
    case Returned = 'returned';
    case Damaged = 'damaged';
    case Blocked = 'blocked';
}
