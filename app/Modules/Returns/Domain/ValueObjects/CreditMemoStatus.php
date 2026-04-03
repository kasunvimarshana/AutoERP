<?php

declare(strict_types=1);

namespace Modules\Returns\Domain\ValueObjects;

class CreditMemoStatus
{
    const DRAFT  = 'draft';
    const ISSUED = 'issued';
    const APPLIED = 'applied';
    const VOIDED = 'voided';

    public static function values(): array
    {
        return ['draft', 'issued', 'applied', 'voided'];
    }
}
