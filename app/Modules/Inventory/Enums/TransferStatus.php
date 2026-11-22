<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Inventory\Enums;

enum TransferStatus: string
{
    case Pending = 'pending';
    case Draft = 'draft';
    case Approved = 'approved';
    case Dispatched = 'dispatched';
    case InTransit = 'in_transit';
    case Received = 'received';
    case Posted = 'posted';
    case Reversed = 'reversed';
    case Cancelled = 'cancelled';
}
