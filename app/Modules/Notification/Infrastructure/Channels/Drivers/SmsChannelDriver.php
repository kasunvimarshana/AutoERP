<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Channels\Drivers;

use Modules\Notification\Domain\Entities\Notification;
use Modules\Notification\Infrastructure\Channels\NotificationChannelInterface;

/**
 * SMS channel driver stub.
 *
 * Replace the body with integration code for Twilio, Vonage, AWS SNS, etc.
 */
class SmsChannelDriver implements NotificationChannelInterface
{
    public function send(Notification $notification): void
    {
        // Placeholder: integrate with Twilio, Vonage, AWS SNS, etc.
    }
}
