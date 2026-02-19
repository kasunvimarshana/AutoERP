<?php

declare(strict_types=1);

namespace Modules\Notification\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Notification\Models\Notification;

/**
 * Notification Failed Event
 *
 * Fired when a notification fails to send
 */
class NotificationFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Notification $notification,
        public string $errorMessage
    ) {}
}
