<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Enums;

enum StockMovementDirection: string
{
    case In = 'IN';
    case Out = 'OUT';
}
