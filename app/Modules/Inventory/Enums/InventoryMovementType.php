<?php

declare(strict_types=1);

namespace Modules\Inventory\Enums;

enum InventoryMovementType: string
{
    case Receipt = 'receipt';
    case Issue = 'issue';
    case TransferIn = 'transfer_in';
    case TransferOut = 'transfer_out';
    case AdjustmentIn = 'adjustment_in';
    case AdjustmentOut = 'adjustment_out';
    case ReturnIn = 'return_in';
    case ReturnOut = 'return_out';
    case Reservation = 'reservation';
    case Allocation = 'allocation';
    case Release = 'release';
}
