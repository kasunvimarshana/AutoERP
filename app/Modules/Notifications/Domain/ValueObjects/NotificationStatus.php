<?php

declare(strict_types=1);

namespace Modules\Notifications\Domain\ValueObjects;

enum NotificationStatus: string
{
    case Pending   = 'pending';
    case Sent      = 'sent';
    case Delivered = 'delivered';
    case Read      = 'read';
    case Failed    = 'failed';
}
