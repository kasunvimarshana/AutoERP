<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Enums;

enum SerialStatus: string
{
    case Available = 'AVAILABLE';
    case Sold = 'SOLD';
    case Returned = 'RETURNED';
    case Damaged = 'DAMAGED';
    case Scrapped = 'SCRAPPED';
}
