<?php

declare(strict_types=1);

namespace Modules\Accounting\Enums;

/**
 * Journal Entry Status Enum
 */
enum JournalEntryStatus: string
{
    case Draft = 'draft';
    case Posted = 'posted';
    case Reversed = 'reversed';
    case Void = 'void';
}
