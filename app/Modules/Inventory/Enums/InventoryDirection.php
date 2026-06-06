<?php

declare(strict_types=1);

namespace Modules\Inventory\Enums;

enum InventoryDirection: string
{
    case In = 'in';
    case Out = 'out';
    case Neutral = 'neutral';
}
