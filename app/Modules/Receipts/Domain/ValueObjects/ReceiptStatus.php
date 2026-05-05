<?php

declare(strict_types=1);

namespace Modules\Receipts\Domain\ValueObjects;

enum ReceiptStatus: string
{
    case Issued = 'issued';
    case Voided = 'voided';
}
