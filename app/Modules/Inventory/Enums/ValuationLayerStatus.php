<?php

declare(strict_types=1);

namespace Modules\Inventory\Enums;

enum ValuationLayerStatus: string
{
    case Open = 'open';
    case Closed = 'closed';
    case Reversed = 'reversed';
}
