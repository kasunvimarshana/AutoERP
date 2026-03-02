<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Enums;

enum TransactionType: string
{
    case Receipt = 'receipt';
    case Shipment = 'shipment';
    case AdjustmentIn = 'adjustment_in';
    case AdjustmentOut = 'adjustment_out';
    case TransferIn = 'transfer_in';
    case TransferOut = 'transfer_out';
    case ReturnIn = 'return_in';

    public function isPositive(): bool
    {
        return in_array($this, [
            self::Receipt,
            self::AdjustmentIn,
            self::TransferIn,
            self::ReturnIn,
        ], true);
    }
}
