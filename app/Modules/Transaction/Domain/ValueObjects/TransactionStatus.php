<?php

declare(strict_types=1);

namespace Modules\Transaction\Domain\ValueObjects;

class TransactionStatus
{
    public const DRAFT    = 'draft';
    public const PENDING  = 'pending';
    public const POSTED   = 'posted';
    public const VOIDED   = 'voided';
    public const REVERSED = 'reversed';

    public static function values(): array
    {
        return ['draft', 'pending', 'posted', 'voided', 'reversed'];
    }
}
