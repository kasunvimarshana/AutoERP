<?php

declare(strict_types=1);

namespace Modules\Sales\Enums;

enum SalesCreditNoteStatus: string
{
    case Draft = 'draft';
    case Approved = 'approved';
    case Posting = 'posting';
    case Posted = 'posted';
    case Allocated = 'allocated';
    case Cancelled = 'cancelled';
    case Reversed = 'reversed';
}
