<?php

declare(strict_types=1);

namespace Modules\Notifications\Domain\Events;

use Modules\Notifications\Domain\Entities\Notification;

class NotificationRead
{
    public function __construct(
        public readonly Notification $notification,
    ) {}
}
