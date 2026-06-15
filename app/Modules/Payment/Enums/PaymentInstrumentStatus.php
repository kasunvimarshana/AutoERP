<?php

declare(strict_types=1);

namespace Modules\Payment\Enums;

enum PaymentInstrumentStatus: string
{
    case Pending = 'pending';
    case Initiated = 'initiated';
    case Authorized = 'authorized';
    case Captured = 'captured';
    case Received = 'received';
    case Issued = 'issued';
    case Deposited = 'deposited';
    case Cleared = 'cleared';
    case Settled = 'settled';
    case Refunded = 'refunded';
    case Bounced = 'bounced';
    case Returned = 'returned';
    case Cancelled = 'cancelled';
    case Stale = 'stale';
    case Failed = 'failed';
    case Reversed = 'reversed';
}
