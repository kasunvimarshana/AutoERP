<?php

declare(strict_types=1);

namespace Modules\HR\Domain\ValueObjects;

enum PayrollRunStatus: string
{
    case DRAFT = 'draft';
    case PROCESSING = 'processing';
    case APPROVED = 'approved';
    case PAID = 'paid';
    case CANCELLED = 'cancelled';
}
