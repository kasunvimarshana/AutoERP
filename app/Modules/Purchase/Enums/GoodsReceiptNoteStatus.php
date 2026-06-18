<?php

declare(strict_types=1);

namespace Modules\Purchase\Enums;

enum GoodsReceiptNoteStatus: string
{
    case Draft = 'draft';
    case Posted = 'posted';
    case Reversed = 'reversed';
}
