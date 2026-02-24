<?php

namespace Modules\Accounting\Domain\Enums;

enum JournalEntryStatus: string
{
    case Draft     = 'draft';
    case Posted    = 'posted';
    case Cancelled = 'cancelled';
}
