<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\Events;

use Modules\Notification\Domain\Entities\Notification;

class NotificationFailed
{
    public function __construct(
        public readonly Notification $notification,
        public readonly string       $reason,
    ) {}
}
