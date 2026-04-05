<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\Events;

use Modules\Notification\Domain\Entities\Notification;

class NotificationSent
{
    public function __construct(
        public readonly Notification $notification,
    ) {}
}
