<?php

declare(strict_types=1);

namespace Modules\StockMovement\Domain\ValueObjects;

class MovementType
{
    public const RECEIPT    = 'receipt';
    public const ISSUE      = 'issue';
    public const TRANSFER   = 'transfer';
    public const ADJUSTMENT = 'adjustment';
    public const RETURN_IN  = 'return_in';
    public const RETURN_OUT = 'return_out';

    public static function values(): array
    {
        return ['receipt', 'issue', 'transfer', 'adjustment', 'return_in', 'return_out'];
    }
}
