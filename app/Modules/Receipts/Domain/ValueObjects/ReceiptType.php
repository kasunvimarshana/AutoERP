<?php

declare(strict_types=1);

namespace Modules\Receipts\Domain\ValueObjects;

enum ReceiptType: string
{
    case Payment = 'payment';
    case Refund = 'refund';
    case Adjustment = 'adjustment';
    case Other = 'other';
}
