<?php

declare(strict_types=1);

namespace Modules\Notification\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Notification\Models\Notification;

/**
 * Notification Read Event
 *
 * Fired when a notification is marked as read
 */
class NotificationRead
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Notification $notification
    ) {}
}
