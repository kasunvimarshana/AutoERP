<?php

namespace App\Modules\Manufacturing\Enums;

enum ProductionOrderStatus: string
{
    case DRAFT = 'draft';
    case RELEASED = 'released';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::RELEASED => 'Released',
            self::IN_PROGRESS => 'In Progress',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function canEdit(): bool
    {
        return in_array($this, [self::DRAFT]);
    }

    public function canRelease(): bool
    {
        return $this === self::DRAFT;
    }

    public function canStart(): bool
    {
        return $this === self::RELEASED;
    }

    public function canComplete(): bool
    {
        return $this === self::IN_PROGRESS;
    }

    public function canCancel(): bool
    {
        return in_array($this, [self::DRAFT, self::RELEASED]);
    }
}
