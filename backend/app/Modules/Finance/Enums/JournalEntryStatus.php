<?php

namespace App\Modules\Finance\Enums;

enum JournalEntryStatus: string
{
    case DRAFT = 'draft';
    case POSTED = 'posted';
    case VOID = 'void';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::POSTED => 'Posted',
            self::VOID => 'Void',
        };
    }

    public function canEdit(): bool
    {
        return $this === self::DRAFT;
    }

    public function canPost(): bool
    {
        return $this === self::DRAFT;
    }

    public function canVoid(): bool
    {
        return $this === self::POSTED;
    }
}
