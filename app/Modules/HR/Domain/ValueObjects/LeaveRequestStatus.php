<?php

declare(strict_types=1);

namespace Modules\HR\Domain\ValueObjects;

enum LeaveRequestStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';
    case RECALLED = 'recalled';
}
