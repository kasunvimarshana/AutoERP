<?php

declare(strict_types=1);

namespace Modules\Manufacturing\Domain\Enums;

enum ProductionStatus: string
{
    case DRAFT      = 'draft';
    case CONFIRMED  = 'confirmed';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED  = 'completed';
    case CANCELLED  = 'cancelled';

    public function isEditable(): bool
    {
        return $this === self::DRAFT;
    }

    public function canStart(): bool
    {
        return $this === self::CONFIRMED;
    }

    public function canComplete(): bool
    {
        return $this === self::IN_PROGRESS;
    }
}
