<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Channels\Drivers;

use Modules\Notification\Domain\Entities\Notification;
use Modules\Notification\Infrastructure\Channels\NotificationChannelInterface;

/**
 * Push notification channel driver stub.
 *
 * Replace the body with integration code for Firebase FCM, Apple APNs,
 * OneSignal, Pusher Beams, etc.
 */
class PushChannelDriver implements NotificationChannelInterface
{
    public function send(Notification $notification): void
    {
        // Placeholder: integrate with FCM, APNs, OneSignal, Pusher Beams, etc.
    }
}
