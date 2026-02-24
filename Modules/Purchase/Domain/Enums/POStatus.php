<?php
namespace Modules\Purchase\Domain\Enums;
enum POStatus: string
{
    case Draft = 'draft';
    case Approved = 'approved';
    case Sent = 'sent';
    case PartiallyReceived = 'partially_received';
    case FullyReceived = 'fully_received';
    case Billed = 'billed';
    case Closed = 'closed';
    case Cancelled = 'cancelled';
}
