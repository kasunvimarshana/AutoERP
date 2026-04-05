<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Channels\Drivers;

use Modules\Notification\Domain\Entities\Notification;
use Modules\Notification\Infrastructure\Channels\NotificationChannelInterface;

/**
 * Email channel driver.
 *
 * In a real deployment this would use Laravel's Mail facade or a dedicated
 * mailer service.  The stub below provides a hook that can be replaced with
 * any mail transport without touching other module code.
 */
class EmailChannelDriver implements NotificationChannelInterface
{
    public function send(Notification $notification): void
    {
        // Placeholder: integrate with Laravel Mail, SendGrid, Mailgun, SES, etc.
        // mail(
        //     $recipientEmail,
        //     $notification->getTitle(),
        //     $notification->getBody(),
        // );
    }
}
