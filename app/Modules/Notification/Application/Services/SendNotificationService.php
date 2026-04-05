<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Services;

use Modules\Notification\Application\Contracts\SendNotificationServiceInterface;
use Modules\Notification\Domain\Entities\Notification;
use Modules\Notification\Domain\RepositoryInterfaces\NotificationRepositoryInterface;
use Modules\Notification\Domain\RepositoryInterfaces\NotificationPreferenceRepositoryInterface;
use Modules\Notification\Domain\RepositoryInterfaces\NotificationTemplateRepositoryInterface;
use Modules\Notification\Domain\ValueObjects\NotificationChannel;
use Modules\Notification\Domain\ValueObjects\NotificationStatus;
use Modules\Notification\Infrastructure\Channels\NotificationChannelDispatcher;

class SendNotificationService implements SendNotificationServiceInterface
{
    public function __construct(
        private readonly NotificationRepositoryInterface         $notifications,
        private readonly NotificationTemplateRepositoryInterface $templates,
        private readonly NotificationPreferenceRepositoryInterface $preferences,
        private readonly NotificationChannelDispatcher           $dispatcher,
    ) {}

    public function send(
        int    $tenantId,
        int    $userId,
        string $type,
        string $channel,
        string $title,
        string $body,
        array  $variables = [],
        array  $data      = [],
    ): Notification {
        // 1. Respect user opt-out preferences (default: enabled)
        $preference = $this->preferences->findByUserAndType(
            $tenantId, $userId, $type, $channel
        );
        $enabled = $preference === null || $preference->isEnabled();

        // 2. Apply template if one exists for this type + channel
        $template = $this->templates->findByTypeAndChannel($tenantId, $type, $channel);
        if ($template !== null && $template->isActive()) {
            $rendered = $template->render($variables);
            $title    = $rendered['subject'];
            $body     = $rendered['body'];
        }

        // 3. Persist the notification record (pending)
        $notification = new Notification(
            null,
            $tenantId,
            $userId,
            $type,
            NotificationChannel::fromString($channel),
            $title,
            $body,
            $data ?: null,
            NotificationStatus::pending(),
            null,
            null,
            new \DateTime(),
            new \DateTime(),
        );
        $notification = $this->notifications->save($notification);

        // 4. If user has opted out, skip dispatch but keep the record
        if (!$enabled) {
            return $notification;
        }

        // 5. Dispatch through the channel
        try {
            $this->dispatcher->dispatch($notification);
            $notification->markAsSent(new \DateTime());
        } catch (\Throwable $e) {
            $notification->markAsFailed();
        }

        return $this->notifications->save($notification);
    }
}
