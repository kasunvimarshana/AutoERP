<?php

declare(strict_types=1);

namespace Modules\Payment\Enums;

enum AllocationStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Reversed = 'reversed';
    case Void = 'void';
}
