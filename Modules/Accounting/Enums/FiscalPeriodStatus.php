<?php

declare(strict_types=1);

namespace Modules\Accounting\Enums;

/**
 * Fiscal Period Status Enum
 */
enum FiscalPeriodStatus: string
{
    case Open = 'open';
    case Closed = 'closed';
    case Locked = 'locked';
}
