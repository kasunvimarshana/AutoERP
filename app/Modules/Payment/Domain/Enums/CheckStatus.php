<?php

declare(strict_types=1);

namespace Modules\Payment\Domain\Enums;

enum CheckStatus: string
{
    case Pending = 'pending';
    case Deposited = 'deposited';
    case Cleared = 'cleared';
    case Bounced = 'bounced';
    case Cancelled = 'cancelled';
}
