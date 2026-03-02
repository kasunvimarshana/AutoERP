<?php

declare(strict_types=1);

namespace Modules\Accounting\Domain\Enums;

enum JournalEntryStatus: string
{
    case Draft = 'draft';
    case Posted = 'posted';
    case Reversed = 'reversed';

    public function isPostable(): bool
    {
        return $this === self::Draft;
    }

    public function isReversible(): bool
    {
        return $this === self::Posted;
    }
}
