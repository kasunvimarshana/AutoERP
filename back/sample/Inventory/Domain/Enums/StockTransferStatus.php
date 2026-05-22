<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Enums;

enum StockTransferStatus: string
{
    case Draft = 'DRAFT';
    case Pending = 'PENDING';
    case Completed = 'COMPLETED';
    case Cancelled = 'CANCELLED';
}
