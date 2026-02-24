<?php

namespace Modules\HR\Domain\Enums;

enum PayrollStatus: string
{
    case DRAFT      = 'draft';
    case PROCESSING = 'processing';
    case COMPLETED  = 'completed';
    case CANCELLED  = 'cancelled';
}
