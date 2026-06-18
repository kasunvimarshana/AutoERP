<?php

declare(strict_types=1);

namespace Modules\Purchase\Enums;

enum GoodsReceiptNoteLineStatus: string
{
    case Open = 'open';
    case Posted = 'posted';
    case Reversed = 'reversed';
}
