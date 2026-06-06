<?php

declare(strict_types=1);

namespace Modules\Finance\Enums;

enum JournalStatus: string
{
    case Draft = 'draft';
    case Posted = 'posted';
    case Reversed = 'reversed';
    case Void = 'void';
    case Cancelled = 'cancelled';
}
