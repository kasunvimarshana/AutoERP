<?php

declare(strict_types=1);

namespace Modules\Inventory\Enums;

enum TransferStatus: string
{
    case Draft = 'draft';
    case Approved = 'approved';
    case InTransit = 'in_transit';
    case Received = 'received';
    case Posted = 'posted';
    case Reversed = 'reversed';
    case Cancelled = 'cancelled';
}
