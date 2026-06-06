<?php

declare(strict_types=1);

namespace Modules\Purchase\Enums;

enum PurchaseDebitNoteStatus: string
{
    case Draft = 'draft';
    case Approved = 'approved';
    case Posted = 'posted';
    case Allocated = 'allocated';
    case Cancelled = 'cancelled';
    case Reversed = 'reversed';
}
