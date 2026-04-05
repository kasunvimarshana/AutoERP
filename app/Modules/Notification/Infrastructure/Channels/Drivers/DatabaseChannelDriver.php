<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Channels\Drivers;

use Modules\Notification\Domain\Entities\Notification;
use Modules\Notification\Infrastructure\Channels\NotificationChannelInterface;

/**
 * Persists the notification to the database notifications table so it can be
 * retrieved via the inbox API.  No external transport is needed — the record
 * already exists in the DB before this driver is called.
 */
class DatabaseChannelDriver implements NotificationChannelInterface
{
    public function send(Notification $notification): void
    {
        // The notification record is already persisted by SendNotificationService.
        // The database channel simply acts as a no-op delivery mechanism since
        // "delivery" means the row is readable by the user's inbox endpoint.
    }
}
